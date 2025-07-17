<?php
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

$tx_ref = $_GET['tx_ref'] ?? '';
$lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
$order_id  = isset($_GET['order_id'])  ? intval($_GET['order_id'])  : 0;

$userName = $_SESSION['users']['name'] ?? 'User';
$type = '';
$title = '';
$backLink = '#';

// 🟩 LESSON Payment
if ($lesson_id > 0) {
    $stmt = $conn->prepare("SELECT title FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $title = $row['title'];
        $type = 'lesson';
        $backLink = 'lessons.php';
    }
    $stmt->close();
}

// 🟦 ORDER Payment
elseif ($order_id > 0) {
    $stmt = $conn->prepare("
        SELECT s.name AS service_name 
        FROM orders o 
        JOIN services s ON o.service_id = s.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $title = $row['service_name']; // will now be like "Photocopying and printing"
        $type = 'order';
        $backLink = 'status.php';
    } else {
        $title = "Unknown Service";
    }
    $stmt->close();
}


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
   <p><strong><?= htmlspecialchars($userName) ?></strong>, your payment for the
    <?= $type === 'lesson' ? 'lesson' : ($type === 'order' ? 'service order' : 'item') ?>:</p>
<p><strong>"<?= htmlspecialchars($title ?: 'N/A') ?>"</strong></p>
    <p>was successful!</p>
    <p>Transaction Ref: <strong><?= htmlspecialchars($tx_ref) ?></strong></p>
    <a href="<?= $backLink ?>">🔙 Go Back</a>
</div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
