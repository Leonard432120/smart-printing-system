<?php
session_start();
include '../includes/db_connect.php';
include '../includes/header.php';

$tx_ref = $_GET['tx_ref'] ?? '';
$lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
$userName = $_SESSION['users']['name'] ?? '';

// Validate tx_ref and lesson_id
if (!$tx_ref || !$lesson_id) {
    echo "<p style='color:red; text-align:center;'>Invalid transaction or lesson.</p>";
    exit;
}

// Fetch lesson title
$lessonTitle = "Lesson";
$stmt = $conn->prepare("SELECT title FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $lessonTitle = $row['title'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f9f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header, footer {
            background-color: #0a3d62;
            color: white;
            padding: 15px;
            text-align: center;
        }
        main {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .success-box {
            text-align: center;
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .success-box h1 {
            color: green;
            margin-bottom: 10px;
        }
        .success-box p {
            font-size: 1.1rem;
            margin: 10px 0;
        }
        .success-box a {
            margin-top: 20px;
            display: inline-block;
            background: #0a3d62;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .success-box a:hover {
            background-color: #064173;
        }
    </style>
</head>
<body>

<main>
<div class="success-box">
    <h1>✅ Payment Successful</h1>
    <p><strong><?= htmlspecialchars($userName) ?></strong>, your payment for the lesson:</p>
    <p><strong>"<?= htmlspecialchars($lessonTitle) ?>"</strong></p>
    <p>was successful!</p>
    <p>Transaction Ref: <strong><?= htmlspecialchars($tx_ref) ?></strong></p>
    <a href="lessons.php">📘 Go Back to Lessons</a>
</div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
