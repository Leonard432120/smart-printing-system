<?php
session_start();
include '../../includes/db_connect.php';
include '../includes/functions.php';

if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $lesson_id = intval($_POST['lesson_id']);
    $title = trim($_POST['title']);
    $book = $_FILES['book'];

    if ($book['error'] === 0) {
        $filename = basename($book['name']);
        $targetDir = "../../uploads/books/";
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($book['tmp_name'], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO lesson_materials (lesson_id, title, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $lesson_id, $title, $filename);
            $stmt->execute();
            $statusMsg = "✅ Book uploaded successfully.";
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
    <title>Upload Books</title>
    <link rel="stylesheet" href="/smart-printing-system/admin/assets/css/upload_pages.css">
</head>
<body>
    
<?php include '../includes/header.php'; ?>
<h2>Upload Book</h2>
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
    <label>Book Title:</label>
    <input type="text" name="title" required><br>
    <label>Upload Book (PDF, DOCX):</label>
    <input type="file" name="book" accept=".pdf,.docx" required><br>
    <button type="submit">Upload Book</button>
</form>
<p><?= $statusMsg ?></p>
<?php include '../includes/footer.php'; ?>

</body>
</html>