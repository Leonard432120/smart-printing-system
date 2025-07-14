<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include __DIR__ . '/../../user/includes/db_connect.php'; // Adjust path to db_connect.php
include '../includes/functions.php';

if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $lesson_id = intval($_POST['lesson_id']);
    $title = trim($_POST['title']);
    $note = $_FILES['note'];

    if ($note['error'] === 0) {
        $filename = basename($note['name']);
        $targetDir = "../../uploads/notes/";
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($note['tmp_name'], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO lesson_materials (lesson_id, title, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $lesson_id, $title, $filename);
            $stmt->execute();
            $statusMsg = "✅ Note uploaded successfully.";
        } else {
            $statusMsg = "❌ Failed to move uploaded file.";
        }
    } else {
        $statusMsg = "❌ Error with uploaded file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Notes</title>
    <link rel="stylesheet" href="/smart-printing-system/admin/assets/css/upload_pages.css">

</head>
<body>
   
<?php include '../includes/header.php'; ?>
<div class="container">
<h2>Upload Note</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Lesson:</label>
    <select name="lesson_id" required>
        <?php
        $res = $conn->query("SELECT id, title FROM lessons");
        while ($row = $res->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['title']}</option>";
        }
        ?>
    </select><br>
    <label>Note Title:</label>
    <input type="text" name="title" required><br>
    <label>Upload Note (PDF):</label>
    <input type="file" name="note" accept=".pdf" required><br>
    <button type="submit">Upload Note</button>
</form>
<p><?= $statusMsg ?></p>
</div>
<?php include '../includes/footer.php'; ?>


</body>
</html>