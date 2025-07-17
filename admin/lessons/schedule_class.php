<?php
session_start();
require_once __DIR__ . '/../../user/includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';

// Handle form submission to schedule new class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = intval($_POST['lesson_id']);
    $topic = trim($_POST['topic']);
    $date = $_POST['date'];
    $link = trim($_POST['link']);

    if ($lesson_id && $topic && $date && filter_var($link, FILTER_VALIDATE_URL)) {
        $stmt = $conn->prepare("INSERT INTO scheduled_classes (lesson_id, topic, class_date, class_link) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $lesson_id, $topic, $date, $link);
        if ($stmt->execute()) {
            $statusMsg = "<span style='color:green;'>✅ Class scheduled successfully.</span>";
        } else {
            $statusMsg = "<span style='color:red;'>❌ Failed to schedule class: " . htmlspecialchars($conn->error) . "</span>";
        }
    } else {
        $statusMsg = "<span style='color:red;'>❌ Please fill in all fields with valid data.</span>";
    }
}

// Fetch upcoming classes
$query = "SELECT sc.id, sc.topic, sc.class_date, sc.class_link, l.title AS lesson_title 
          FROM scheduled_classes sc 
          JOIN lessons l ON sc.lesson_id = l.id
          WHERE sc.class_date >= NOW()
          ORDER BY sc.class_date ASC";

$result = $conn->query($query);

// Fetch lessons for dropdown
$lessonsRes = $conn->query("SELECT id, title FROM lessons ORDER BY title ASC");
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- main-content starts after sidebar in your header.php -->
<div class="container" style="padding: 20px;"> <!-- Add some padding inside main-content -->

    <h1>Manage Scheduled Classes</h1>

    <!-- Schedule New Class Form -->
    <form method="POST" class="schedule-form" novalidate>
        <?= $statusMsg ?>
        <label for="lesson_id">Select Lesson:</label>
        <select name="lesson_id" id="lesson_id" required>
            <option value="">-- Select Lesson --</option>
            <?php while ($lesson = $lessonsRes->fetch_assoc()): ?>
                <option value="<?= $lesson['id'] ?>"><?= htmlspecialchars($lesson['title']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="topic">Class Topic:</label>
        <input type="text" name="topic" id="topic" required placeholder="e.g., Introduction to PHP">

        <label for="date">Class Date & Time:</label>
        <input type="datetime-local" name="date" id="date" required>

        <label for="link">Class Link (Zoom, Google Meet, etc.):</label>
        <input type="url" name="link" id="link" required placeholder="https://">

        <button type="submit">Schedule Class</button>
    </form>

    <h2>Upcoming Classes</h2>
    <?php if ($result->num_rows === 0): ?>
        <p>No upcoming classes scheduled.</p>
    <?php else: ?>
        <?php while ($class = $result->fetch_assoc()):
            $classDate = $class['class_date'];
            $timestamp = strtotime($classDate);
            $nowTimestamp = time();

            // Class is live if current time is within class date +/- 1 hour (example)
            $isLive = ($nowTimestamp >= $timestamp && $nowTimestamp <= ($timestamp + 3600));
            ?>
            <div class="class-card">
                <div class="class-info">
                    <div class="class-topic">
                        <?= htmlspecialchars($class['topic']) ?>
                        <?php if ($isLive): ?>
                            <span class="live-badge">LIVE NOW</span>
                        <?php endif; ?>
                    </div>
                    <div class="class-lesson"><?= htmlspecialchars($class['lesson_title']) ?></div>
                    <div class="class-date"><?= date('D, M j, Y \a\t g:i A', $timestamp) ?></div>
                </div>
                <a href="<?= htmlspecialchars($class['class_link']) ?>" target="_blank" class="join-btn">
                    Join Class
                </a>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
<style>
    /* Container adjustments */
.container {
    max-width: 900px;
    margin: 30px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #2c3e50;
}

/* Headings */
.container h1, .container h2 {
    color: #0a3d62;
    font-weight: 700;
    margin-bottom: 20px;
}

/* Form styling */
.schedule-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-bottom: 40px;
}

.schedule-form label {
    font-weight: 600;
    color: #0a3d62;
}

.schedule-form input[type="text"],
.schedule-form input[type="datetime-local"],
.schedule-form input[type="url"],
.schedule-form select {
    padding: 12px 14px;
    border: 1.5px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fcfcfc;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.schedule-form input[type="text"]:focus,
.schedule-form input[type="datetime-local"]:focus,
.schedule-form input[type="url"]:focus,
.schedule-form select:focus {
    border-color: #0a3d62;
    box-shadow: 0 0 8px rgba(10, 61, 98, 0.25);
    outline: none;
}

/* Submit button */
.schedule-form button[type="submit"] {
    align-self: flex-start;
    background-color: #0a3d62;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.schedule-form button[type="submit"]:hover {
    background-color: #064173;
}

/* Status messages */
.schedule-form span {
    font-weight: 700;
    font-size: 1rem;
}

/* Upcoming Classes Cards */
.class-card {
    padding: 18px 25px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: box-shadow 0.3s ease;
}

.class-card:hover {
    box-shadow: 0 4px 12px rgba(10, 61, 98, 0.1);
}

.class-info {
    max-width: 75%;
}

.class-topic {
    font-weight: 700;
    font-size: 1.2rem;
    color: #0a3d62;
}

.class-lesson {
    font-size: 1rem;
    color: #6c757d;
    margin-top: 4px;
}

.class-date {
    margin-top: 6px;
    font-weight: 600;
    color: #34495e;
}

/* Join button */
.join-btn {
    background-color: #0a3d62;
    color: #fff;
    padding: 12px 22px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    transition: background-color 0.3s ease;
    white-space: nowrap;
}

.join-btn:hover {
    background-color: #064173;
}

/* Live badge */
.live-badge {
    background-color: #e74c3c;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    margin-left: 10px;
    vertical-align: middle;
}

</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
