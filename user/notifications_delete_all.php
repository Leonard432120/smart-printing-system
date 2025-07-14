<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'includes/db_connect.php';

$user_contact = $_SESSION['users']['email'];

$stmt = $conn->prepare("DELETE FROM notifications WHERE recipient_contact = ?");
$stmt->bind_param("s", $user_contact);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete notifications.',
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
