<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['users'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'includes/db_connect.php';

$user_contact = $_SESSION['user']['email'];

$stmt = $conn->prepare("UPDATE notifications SET sent = 1 WHERE recipient_contact = ?");
$stmt->bind_param("s", $user_contact);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
