<?php
session_start();
include 'includes/db_connect.php';

// Get URL parameters
$tx_ref = $_GET['tx_ref'] ?? '';
$lesson_id = $_GET['lesson_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if (!$tx_ref) {
    die("Transaction reference is missing.");
}

// Fetch payment info for display and validation
$stmt = $conn->prepare("SELECT * FROM payments WHERE transaction_id = ?");
$stmt->bind_param("s", $tx_ref);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Payment not found.");
}

// Get user email for welcome message
$userEmail = $payment['email'] ?? '';

// Prepare info message
$message = "";
if ($lesson_id) {
    // Fetch lesson title for confirmation
    $stmt = $conn->prepare("SELECT title FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $lessonTitle = $lesson['title'] ?? "Your lesson";

    $message = "Thank you! Your payment for <strong>" . htmlspecialchars($lessonTitle) . "</strong> was successful. You are now enrolled.";
}
elseif ($order_id) {
    // Fetch service name for confirmation
    $stmt = $conn->prepare("SELECT s.name FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $serviceName = $order['name'] ?? "your service order";

    $message = "Thank you! Your payment for <strong>" . htmlspecialchars($serviceName) . "</strong> has been received. Your order is now confirmed.";
}
else {
    $message = "Thank you! Your payment was successful.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payment Success | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-image: url('/smart-printing-system/assets/images/Background.jpeg');
      background-size: cover;
      background-position: center;
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .success-container {
      background: #fff;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      max-width: 500px;
      text-align: center;
    }

    .success-container h1 {
      color: #0a3d62;
      margin-bottom: 20px;
    }

    .success-container p {
      font-size: 1.2rem;
      margin-bottom: 30px;
    }

    a.button {
      text-decoration: none;
      background-color: #0a3d62;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      transition: background-color 0.3s;
    }

    a.button:hover {
      background-color: #064173;
    }
  </style>
</head>
<body>
  <div class="success-container">
    <h1>Payment Successful!</h1>
    <p><?= $message ?></p>
    <a href="/smart-printing-system/user/lessons/lessons.php" class="button">Go to Lessons</a>
    <br><br>
    <a href="/smart-printing-system/user/services.php" class="button" style="background-color: #28a745;">View Services</a>
  </div>
</body>
</html>
