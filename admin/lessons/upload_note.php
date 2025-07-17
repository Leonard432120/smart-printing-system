<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../user/includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect non-admin users
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$statusMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_id'])) {
    $lesson_id = intval($_POST['lesson_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $note = $_FILES['note'] ?? null;
    $thumbnail = $_FILES['thumbnail'] ?? null;
    $thumbnailFilename = 'assets/images/pdf_icon.png'; // default

    $uploadDirNotes = __DIR__ . '/../../uploads/notes/';
    $uploadDirThumbs = __DIR__ . '/../../assets/images/';

    // Ensure directories exist
    if (!is_dir($uploadDirNotes)) mkdir($uploadDirNotes, 0777, true);
    if (!is_dir($uploadDirThumbs)) mkdir($uploadDirThumbs, 0777, true);

    // Validate and upload note
    if ($note && $note['error'] === 0) {
        $allowedExt = ['pdf'];
        $fileExt = strtolower(pathinfo($note['name'], PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedExt)) {
            $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($note['name']));
            $targetFile = $uploadDirNotes . $filename;

            if (move_uploaded_file($note['tmp_name'], $targetFile)) {
                // Optional thumbnail
                if ($thumbnail && $thumbnail['error'] === 0) {
                    $thumbExt = strtolower(pathinfo($thumbnail['name'], PATHINFO_EXTENSION));
                    $allowedThumbs = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($thumbExt, $allowedThumbs)) {
                        $thumbnailFilenameOnly = 'thumb_' . time() . '.' . $thumbExt;
                        $thumbTarget = $uploadDirThumbs . $thumbnailFilenameOnly;

                        if (move_uploaded_file($thumbnail['tmp_name'], $thumbTarget)) {
                            $thumbnailFilename = 'assets/images/' . $thumbnailFilenameOnly;
                        }
                    }
                }

                // Save to DB
                $stmt = $conn->prepare("INSERT INTO lesson_materials (lesson_id, title, description, file_path, thumbnail) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $lesson_id, $title, $description, $filename, $thumbnailFilename);

                $statusMsg = $stmt->execute()
                    ? "✅ Note uploaded successfully."
                    : "❌ Database error: " . $conn->error;

                $stmt->close();
            } else {
                $statusMsg = "❌ Failed to move uploaded PDF.";
            }
        } else {
            $statusMsg = "❌ Only PDF files are allowed.";
        }
    } else {
        $errors = [
            1 => "File exceeds upload_max_filesize.",
            2 => "File exceeds MAX_FILE_SIZE.",
            3 => "File only partially uploaded.",
            4 => "No file uploaded.",
            6 => "Missing temporary folder.",
            7 => "Failed to write to disk.",
            8 => "PHP extension stopped upload."
        ];
        $uploadError = $note['error'] ?? 4;
        $statusMsg = "❌ Upload error: " . ($errors[$uploadError] ?? "Unknown error.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Upload Lesson Notes | Admin Panel</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            padding: 30px 20px;
        }

        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-card label {
            display: block;
            margin-top: 20px;
            font-weight: 600;
            color: #34495e;
        }

        .form-card input,
        .form-card select,
        .form-card textarea {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #fdfdfd;
        }

        .form-card input:focus,
        .form-card select:focus,
        .form-card textarea:focus {
            border-color: #2980b9;
            box-shadow: 0 0 8px rgba(41, 128, 185, 0.3);
            outline: none;
        }

        .form-card textarea {
            resize: vertical;
            min-height: 120px;
        }

        .action-btn {
            margin-top: 30px;
            padding: 14px;
            background-color: #2980b9;
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: #1f6391;
        }

        .status-msg {
            max-width: 700px;
            margin: 25px auto;
            padding: 16px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .status-msg.error {
            background-color: #ffe6e6;
            color: #c0392b;
            border: 1px solid #e74c3c;
        }

        .status-msg.success {
            background-color: #e6f9f0;
            color: #27ae60;
            border: 1px solid #2ecc71;
        }

        .dashboard-header {
            max-width: 900px;
            margin: 0 auto 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
        }

        .dashboard-header p {
            color: #7f8c8d;
            margin-top: 6px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
        }

        .user-profile i {
            font-size: 1.6rem;
            color: #3498db;
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="dashboard-header">
        <div>
            <h1><i class="fas fa-upload"></i> Upload Lesson Notes</h1>
            <p>Attach PDFs and optional thumbnails for students</p>
        </div>
        <div class="user-profile">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['users']['username']) ?></span>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="form-card" novalidate>
        <label for="thumbnail">Optional Thumbnail (JPG, PNG, GIF):</label>
        <input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.gif" id="thumbnail">

        <label for="lesson_id">Select Lesson:</label>
        <select name="lesson_id" id="lesson_id" required>
            <option value="">-- Select Lesson --</option>
            <?php
            $res = $conn->query("SELECT id, title FROM lessons ORDER BY title ASC");
            while ($row = $res->fetch_assoc()) {
                echo "<option value='" . intval($row['id']) . "'>" . htmlspecialchars($row['title']) . "</option>";
            }
            ?>
               </select>

        <label for="title">Note Title:</label>
        <input type="text" name="title" id="title" required placeholder="e.g., Chapter 1 - Introduction" maxlength="255">

        <label for="description">Note Description:</label>
        <textarea name="description" id="description" required placeholder="Short summary about the note..." maxlength="500"></textarea>

        <label for="note">Upload PDF Note:</label>
        <input type="file" name="note" accept=".pdf" id="note" required>

        <button type="submit" class="action-btn">📤 Upload Note</button>
    </form>

    <?php if ($statusMsg): ?>
        <div class="status-msg <?= str_starts_with($statusMsg, '❌') ? 'error' : 'success' ?>">
            <?= htmlspecialchars($statusMsg) ?>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
