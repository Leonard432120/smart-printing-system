<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['users'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

include 'includes/db_connect.php';

// Get ID from JSON input
$data = json_decode(file_get_contents("php://input"), true);
$notificationId = $data['id'] ?? null;

if (!$notificationId) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND recipient_contact = ?");
$stmt->bind_param("is", $notificationId, $_SESSION['user']['email']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete']);
}
?>
