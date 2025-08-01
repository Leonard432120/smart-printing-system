<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Africa/Nairobi');

session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

$user_id = $_SESSION['users']['id'];
$user_name = $_SESSION['users']['name'];
$user_role = strtolower($_SESSION['users']['role'] ?? '');
$lesson_id = intval($_GET['lesson_id'] ?? 0);
$class_id = intval($_GET['class_id'] ?? 0);

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $parent_id = intval($_POST['parent_id'] ?? 0);
    if ($message) {
        $stmt = $conn->prepare("INSERT INTO class_discussions (class_id, user_id, parent_id, message, posted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $class_id, $user_id, $parent_id, $message);
        $stmt->execute();
        // Refresh to show new post
        header("Location: class_discussion.php?lesson_id=$lesson_id&class_id=$class_id");
        exit();
    }
}

// Fetch class details
$stmt = $conn->prepare("SELECT * FROM scheduled_classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    echo "Class not found.";
    exit();
}

// Fetch discussions with user details
$discussion_stmt = $conn->prepare("
    SELECT cd.*, u.name, u.role,
           (SELECT COUNT(*) FROM class_discussions cd2 WHERE cd2.parent_id = cd.id) as reply_count
    FROM class_discussions cd
    JOIN users u ON cd.user_id = u.id
    WHERE cd.class_id = ?
    ORDER BY cd.posted_at ASC
");
$discussion_stmt->bind_param("i", $class_id);
$discussion_stmt->execute();
$discussions = $discussion_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build discussion tree
$discussion_tree = [];
foreach ($discussions as $discussion) {
    if ($discussion['parent_id'] == 0) {
        $discussion_tree[$discussion['id']] = $discussion;
        $discussion_tree[$discussion['id']]['replies'] = [];
    }
}
foreach ($discussions as $discussion) {
    if ($discussion['parent_id'] != 0 && isset($discussion_tree[$discussion['parent_id']])) {
        $discussion_tree[$discussion['parent_id']]['replies'][] = $discussion;
    }
}

// Function to display threads
function renderDiscussion($discussion, $class_id, $user_role, $depth = 0) {
    $max_depth = 4;
    $is_lecturer = $discussion['role'] === 'lecturer';
    ?>
    <div class="comment" style="margin-left: <?= $depth * 40 ?>px;">
        <div class="comment-header">
            <div class="comment-author">
                <div class="author-avatar <?= $is_lecturer ? 'lecturer' : '' ?>">
                    <?= strtoupper(substr($discussion['name'], 0, 1)) ?>
                </div>
                <div class="author-info">
                    <span class="author-name"><?= htmlspecialchars($discussion['name']) ?></span>
                    <?php if ($is_lecturer): ?>
                        <span class="author-badge">Lecturer</span>
                    <?php endif; ?>
                    <span class="comment-time"><?= date('M j, Y \a\t g:i a', strtotime($discussion['posted_at'])) ?></span>
                </div>
            </div>
        </div>
        <div class="comment-content">
            <?= nl2br(htmlspecialchars($discussion['message'])) ?>
        </div>
        <div class="comment-actions">
            <?php if ($depth < $max_depth): ?>
                <button class="reply-btn" data-comment="<?= $discussion['id'] ?>">
                    <i class="fas fa-reply"></i> Reply
                </button>
            <?php endif; ?>
        </div>

        <?php if ($depth < $max_depth): ?>
            <div class="reply-form" id="reply-form-<?= $discussion['id'] ?>" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <input type="hidden" name="parent_id" value="<?= $discussion['id'] ?>">
                    <textarea name="message" placeholder="Write your reply..." required></textarea>
                    <button type="submit" class="submit-btn">Post Reply</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($discussion['replies'])): ?>
            <div class="replies">
                <?php foreach ($discussion['replies'] as $reply): ?>
                    <?php renderDiscussion($reply, $class_id, $user_role, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Discussion - <?= htmlspecialchars($class['topic']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --text-color: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --bg-color: #ffffff;
            --page-bg: #f8fafc;
            --lecturer-color: #dc2626;
            --student-color: #2563eb;
            --discussion-bg: #f1f5f9;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.1);
            --card-shadow-hover: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-color);
            background-color: var(--page-bg);
            line-height: 1.5;
            font-size: 16px;
        }
        
        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .discussion-header {
            margin-bottom: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: var(--primary-hover);
        }
        
        .discussion-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .class-meta {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .new-comment-card {
            background: var(--bg-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .new-comment-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .new-comment textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            min-height: 120px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }
        
        .new-comment textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-hover);
        }
        
        .comments-section {
            background-color: var(--bg-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }
        
        .comment {
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            border-radius: 0.5rem;
            background-color: var(--bg-color);
            box-shadow: var(--card-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .comment:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .comment-header {
            margin-bottom: 0.75rem;
        }
        
        .comment-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .author-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: var(--student-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        .author-avatar.lecturer {
            background-color: var(--lecturer-color);
        }
        
        .author-info {
            display: flex;
            flex-direction: column;
        }
        
        .author-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .author-badge {
            background-color: #f1f5f9;
            color: var(--text-light);
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            margin-top: 0.25rem;
            align-self: flex-start;
        }
        
        .comment-time {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .comment-content {
            margin-left: 3.25rem;
            font-size: 0.95rem;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .comment-actions {
            margin-left: 3.25rem;
            margin-top: 1rem;
        }
        
        .reply-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        
        .reply-btn:hover {
            color: var(--primary-hover);
        }
        
        .reply-form {
            margin-left: 3.25rem;
            margin-top: 1rem;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            min-height: 80px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            margin-bottom: 0.75rem;
        }
        
        .replies {
            margin-top: 1.5rem;
            padding-left: 1.5rem;
            border-left: 2px solid var(--border-color);
        }
        
        .no-comments {
            color: var(--text-light);
            text-align: center;
            padding: 3rem 0;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .discussion-title {
                font-size: 1.5rem;
            }
            
            .comment {
                padding: 1rem;
            }
            
            .comment-content,
            .comment-actions,
            .reply-form {
                margin-left: 0;
                padding-left: 3.25rem;
            }
            
            .replies {
                padding-left: 1rem;
            }
        }
    </style>
</head>
<body class="page-container">
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="discussion-header">
                <a href="lesson_details.php?lesson_id=<?= $lesson_id ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Lesson
                </a>
                
                <h1 class="discussion-title"><?= htmlspecialchars($class['topic']) ?></h1>
                <p class="class-meta">Class discussion forum</p>
            </div>
            
            <div class="new-comment-card">
                <h2 class="new-comment-title">Add your comment</h2>
                <form method="POST" class="new-comment">
                    <input type="hidden" name="class_id" value="<?= $class_id ?>">
                    <textarea name="message" placeholder="Share your thoughts, ask questions, or contribute to the discussion..." required></textarea>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Post Comment
                    </button>
                </form>
            </div>
            
            <div class="comments-section">
                <h2 class="section-title">Discussion Thread</h2>
                
                <?php if (!empty($discussion_tree)): ?>
                    <?php foreach ($discussion_tree as $discussion): ?>
                        <?php renderDiscussion($discussion, $class_id, $user_role); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                        <p>No discussions yet. Be the first to comment!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const formId = 'reply-form-' + this.dataset.comment;
            const form = document.getElementById(formId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.querySelector('textarea').focus();
            }
        });
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>