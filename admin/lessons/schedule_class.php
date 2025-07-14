<?php
session_start();
include '../../includes/db_connect.php';
include '../includes/functions.php';

if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = intval($_POST['lesson_id']);
    $topic = trim($_POST['topic']);
    $date = $_POST['date'];
    $link = $_POST['link'];

    $stmt = $conn->prepare("INSERT INTO scheduled_classes (lesson_id, topic, class_date, class_link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $lesson_id, $topic, $date, $link);
    if ($stmt->execute()) {
        $statusMsg = "✅ Class scheduled successfully.";
    } else {
        $statusMsg = "❌ Failed to schedule class.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>schedule class</title>
    <link rel="stylesheet" href="/smart-printing-system/admin/assets/css/upload_pages.css">
</head>
<body>
    
<?php include '../includes/header.php'; ?>
<h2>Schedule a Class</h2>
<form method="POST">
    <label>Lesson:</label>
    <select name="lesson_id" required>
        <?php
        $res = $conn->query("SELECT id, title FROM lessons");
        while ($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['title']}</option>";
        }
        ?>
    </select><br>
    <label>Topic:</label>
    <input type="text" name="topic" required><br>
    <label>Date:</label>
    <input type="datetime-local" name="date" required><br>
    <label>Class Link (Zoom/Google Meet):</label>
    <input type="url" name="link" required><br>
    <button type="submit">Schedule</button>
</form>
<p><?= $statusMsg ?></p>
<?php include '../includes/footer.php'; ?>

</body>
</html>