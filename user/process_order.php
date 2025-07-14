<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    echo "<p style='color:red; text-align:center;'>You must be logged in to place an order.</p>";
    echo "<p><a href='../admin/login.php'>Login</a></p>";
    exit();
}

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: services.php");
    exit();
}

// Retrieve and sanitize input
$user_id    = $_SESSION['users']['id'];
$name       = $_SESSION['users']['name'];
$email      = $_SESSION['users']['email'];
$service_id = (int)($_POST['service_id'] ?? 0);
$full_name  = trim($_POST['full_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$notes      = trim($_POST['notes'] ?? '');
$status     = 'pending';

// Basic validations
if (!$service_id || empty($full_name) || empty($email) || empty($phone)) {
    echo "<p style='color:red; text-align:center;'>All required fields must be filled.</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<p style='color:red; text-align:center;'>Invalid email format.</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    exit();
}

// Save order
$stmt = $conn->prepare("INSERT INTO orders (user_id, service_id, full_name, email, phone, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iisssss", $user_id, $service_id, $full_name, $email, $phone, $notes, $status);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;

    // ✅ Redirect to payment
    header("Location: pay_order.php?order_id=$order_id");
    exit();

} else {
    echo "<p style='color:red; text-align:center;'>Failed to place order. Please try again later.</p>";
    echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    exit();
}
?>
