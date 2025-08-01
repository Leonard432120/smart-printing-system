<?php
session_start();
require_once __DIR__ . '/../../user/includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['users']) || !in_array(strtolower($_SESSION['users']['role']), ['admin', 'lecturer'])) {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';
$user_id = $_SESSION['users']['id'];
$user_role = strtolower($_SESSION['users']['role']);

// Automatically delete classes that ended more than 1 hour ago
$cleanupStmt = $conn->prepare("DELETE FROM scheduled_classes WHERE class_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$cleanupStmt->execute();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'post_discussion':
                $class_id = intval($_POST['class_id']);
                $message = trim($_POST['message']);
                
                if ($class_id && $message) {
                    $stmt = $conn->prepare("INSERT INTO class_discussions (class_id, user_id, message, posted_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("iis", $class_id, $user_id, $message);
                    if ($stmt->execute()) {
                        $statusMsg = "<div class='alert success'>✅ Discussion posted successfully.</div>";
                    } else {
                        $statusMsg = "<div class='alert error'>❌ Failed to post discussion: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
                break;
                
            case 'schedule_class':
                $lesson_id = intval($_POST['lesson_id']);
                $topic = trim($_POST['topic']);
                $date = $_POST['date'];
                $link = trim($_POST['link']);

                $mysqlDate = date('Y-m-d H:i:s', strtotime($date));
                
                if ($lesson_id && $topic && $date && filter_var($link, FILTER_VALIDATE_URL)) {
                    $stmt = $conn->prepare("INSERT INTO scheduled_classes (lesson_id, topic, class_date, class_link) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $lesson_id, $topic, $mysqlDate, $link);
                    if ($stmt->execute()) {
                        $statusMsg = "<div class='alert success'>✅ Class scheduled successfully.</div>";
                    } else {
                        $statusMsg = "<div class='alert error'>❌ Failed to schedule class: " . htmlspecialchars($conn->error) . "</div>";
                    }
                } else {
                    $statusMsg = "<div class='alert error'>❌ Please fill in all fields with valid data.</div>";
                }
                break;
                
            case 'delete_class':
                $class_id = intval($_POST['class_id']);
                if ($class_id) {
                    $stmt = $conn->prepare("DELETE FROM scheduled_classes WHERE id = ?");
                    $stmt->bind_param("i", $class_id);
                    if ($stmt->execute()) {
                        $statusMsg = "<div class='alert success'>✅ Class deleted successfully.</div>";
                    } else {
                        $statusMsg = "<div class='alert error'>❌ Failed to delete class: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
                break;
        }
    }
}

// Fetch classes (including recent past ones)
$query = "SELECT sc.id, sc.topic, sc.class_date, sc.class_link, l.title AS lesson_title 
          FROM scheduled_classes sc 
          JOIN lessons l ON sc.lesson_id = l.id
          WHERE sc.class_date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
          ORDER BY sc.class_date ASC";
$result = $conn->query($query);

// Fetch lessons for dropdown
$lessonsRes = $conn->query("SELECT id, title FROM lessons ORDER BY title ASC");
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Class Management</h1>
        <p>Schedule and manage live classes</p>
    </div>

    <?= $statusMsg ?>

    <div class="card">
        <h2>Schedule New Class</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="schedule_class">
            
            <div class="form-group">
                <label for="lesson_id">Lesson</label>
                <select name="lesson_id" id="lesson_id" required>
                    <option value="">Select Lesson</option>
                    <?php while ($lesson = $lessonsRes->fetch_assoc()): ?>
                        <option value="<?= $lesson['id'] ?>"><?= htmlspecialchars($lesson['title']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="topic">Topic</label>
                <input type="text" name="topic" id="topic" required placeholder="Class topic">
            </div>
            
            <div class="form-group">
                <label for="date">Date & Time</label>
                <input type="datetime-local" name="date" id="date" required min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            
            <div class="form-group">
                <label for="link">Meeting Link</label>
                <input type="url" name="link" id="link" required placeholder="https://zoom.us/...">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-calendar-plus"></i> Schedule Class
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Upcoming Classes</h2>
        
        <?php if ($result->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No classes scheduled yet</p>
            </div>
        <?php else: ?>
            <div class="class-list">
                <?php while ($class = $result->fetch_assoc()):
                    $currentTime = time();
                    $classTime = strtotime($class['class_date']);
                    $tenMinutesBefore = $classTime - (10 * 60);
                    $oneHourAfter = $classTime + (60 * 60);
                    
                    $isPast = ($currentTime > $oneHourAfter);
                    $isUpcoming = ($currentTime < $tenMinutesBefore);
                    $isSoon = ($currentTime >= $tenMinutesBefore && $currentTime < $classTime);
                    $isActive = ($currentTime >= $classTime && $currentTime <= $oneHourAfter);
                    
                    $classDateTimeFormatted = date('D, M j, Y \a\t g:i A', $classTime);
                    
                    // Fetch discussions for this class
                    $discussions = [];
                    $discussionStmt = $conn->prepare("
                        SELECT cd.*, u.name, u.role 
                        FROM class_discussions cd
                        JOIN users u ON cd.user_id = u.id
                        WHERE cd.class_id = ?
                        ORDER BY cd.posted_at DESC
                        LIMIT 3
                    ");
                    $discussionStmt->bind_param("i", $class['id']);
                    $discussionStmt->execute();
                    $discussionResult = $discussionStmt->get_result();
                    while ($row = $discussionResult->fetch_assoc()) {
                        $discussions[] = $row;
                    }
                ?>
                <div class="class-card <?= $isActive ? 'active' : '' ?>">
                    <div class="class-header">
                        <div class="class-meta">
                            <h3><?= htmlspecialchars($class['topic']) ?></h3>
                            <span class="lesson-name"><?= htmlspecialchars($class['lesson_title']) ?></span>
                            <span class="class-time">
                                <i class="fas fa-clock"></i> <?= $classDateTimeFormatted ?>
                            </span>
                        </div>
                        
                        <div class="class-status">
                            <?php if ($isActive): ?>
                                <span class="status-badge live"><i class="fas fa-circle"></i> LIVE NOW</span>
                            <?php elseif ($isSoon): ?>
                                <span class="status-badge soon"><i class="fas fa-clock"></i> STARTING SOON</span>
                            <?php elseif ($isUpcoming): ?>
                                <span class="status-badge upcoming"><i class="fas fa-calendar"></i> UPCOMING</span>
                            <?php else: ?>
                                <span class="status-badge ended"><i class="fas fa-check"></i> COMPLETED</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="class-actions">
                        <?php if ($isActive || $isSoon): ?>
                            <a href="<?= htmlspecialchars($class['class_link']) ?>" target="_blank" class="btn-join">
                                <i class="fas fa-video"></i> Join Class
                            </a>
                        <?php endif; ?>
                        
                        <form method="POST" class="delete-form">
                            <input type="hidden" name="action" value="delete_class">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this class?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                    
                    <div class="class-discussion">
                        <h4><i class="fas fa-comments"></i> Class Discussion</h4>
                        
                        <form method="POST" class="discussion-form">
                            <input type="hidden" name="action" value="post_discussion">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <textarea name="message" placeholder="Post an announcement or answer student questions..." required></textarea>
                            <button type="submit" class="btn-post">
                                <i class="fas fa-paper-plane"></i> Post as <?= ucfirst($user_role) ?>
                            </button>
                        </form>
                        
                        <?php if (!empty($discussions)): ?>
                            <div class="discussion-thread">
                                <?php foreach ($discussions as $post): ?>
                                    <div class="discussion-post <?= $post['role'] === 'lecturer' ? 'lecturer' : '' ?>">
                                        <div class="post-header">
                                            <div class="post-author">
                                                <div class="author-avatar <?= $post['role'] === 'lecturer' ? 'lecturer' : 'student' ?>">
                                                    <?= strtoupper(substr($post['name'], 0, 1)) ?>
                                                </div>
                                                <span class="author-name"><?= htmlspecialchars($post['name']) ?></span>
                                                <span class="author-role">(<?= ucfirst($post['role']) ?>)</span>
                                            </div>
                                            <span class="post-time"><?= date('M j, g:i a', strtotime($post['posted_at'])) ?></span>
                                        </div>
                                        <div class="post-content"><?= nl2br(htmlspecialchars($post['message'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <a href="../../user/class_discussion.php?class_id=<?= $class['id'] ?>" class="view-all">
    <i class="fas fa-comments"></i> View all discussions
</a>
                            </div>
                        <?php else: ?>
                            <div class="no-discussions">
                                <i class="fas fa-comment-slash"></i>
                                <p>No discussions yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    :root {
        --primary: #0a3d62;
        --primary-light: #1e5d8a;
        --secondary: #e74c3c;
        --success: #27ae60;
        --warning: #f39c12;
        --danger: #e74c3c;
        --light: #f8f9fa;
        --dark: #343a40;
        --text: #2c3e50;
        --text-light: #7f8c8d;
        --border: #dfe6e9;
    }
    
    .admin-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    
    .admin-header {
        margin-bottom: 2rem;
    }
    
    .admin-header h1 {
        color: var(--primary);
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .admin-header p {
        color: var(--text-light);
        font-size: 1.1rem;
    }
    
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .card h2 {
        color: var(--primary);
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text);
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(10, 61, 98, 0.1);
        outline: none;
    }
    
    .form-actions {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    
    .btn-primary {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary:hover {
        background-color: var(--primary-light);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 0;
        color: var(--text-light);
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--border);
    }
    
    .class-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .class-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .class-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .class-card.active {
        border-left: 4px solid var(--success);
    }
    
    .class-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .class-meta {
        flex: 1;
    }
    
    .class-meta h3 {
        font-size: 1.25rem;
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    
    .lesson-name {
        display: block;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .class-time {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text);
        font-size: 0.9rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-badge.live {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger);
    }
    
    .status-badge.soon {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning);
    }
    
    .status-badge.upcoming {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .status-badge.ended {
        background-color: rgba(149, 165, 166, 0.1);
        color: var(--text-light);
    }
    
    .class-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .btn-join {
        background-color: var(--success);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.3s;
    }
    
    .btn-join:hover {
        background-color: #219653;
    }
    
    .btn-delete {
        background-color: var(--danger);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.3s;
    }
    
    .btn-delete:hover {
        background-color: #c0392b;
    }
    
    .class-discussion {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
    }
    
    .class-discussion h4 {
        font-size: 1.1rem;
        color: var(--text);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .discussion-form {
        margin-bottom: 1.5rem;
    }
    
    .discussion-form textarea {
        width: 100%;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        min-height: 100px;
        margin-bottom: 1rem;
        resize: vertical;
    }
    
    .btn-post {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.3s;
    }
    
    .btn-post:hover {
        background-color: var(--primary-light);
    }
    
    .discussion-thread {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .discussion-post {
        background-color: var(--light);
        border-radius: 8px;
        padding: 1rem;
    }
    
    .discussion-post.lecturer {
        background-color: #e8f4fd;
        border-left: 3px solid var(--primary);
    }
    
    .post-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .post-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .author-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
    }
    
    .author-avatar.lecturer {
        background-color: var(--primary);
    }
    
    .author-avatar.student {
        background-color: var(--text-light);
    }
    
    .author-name {
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .author-role {
        font-size: 0.85rem;
        color: var(--text-light);
    }
    
    .post-time {
        font-size: 0.85rem;
        color: var(--text-light);
    }
    
    .post-content {
        margin-left: calc(32px + 0.75rem);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .view-all {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        margin-top: 1rem;
    }
    
    .view-all:hover {
        text-decoration: underline;
    }
    
    .no-discussions {
        text-align: center;
        padding: 2rem 0;
        color: var(--text-light);
    }
    
    .no-discussions i {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--border);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    
    .alert.success {
        background-color: rgba(39, 174, 96, 0.1);
        color: var(--success);
        border-left: 4px solid var(--success);
    }
    
    .alert.error {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus the first textarea
    const firstTextarea = document.querySelector('textarea');
    if (firstTextarea) {
        firstTextarea.focus();
    }
    
    // Confirm before deleting
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>