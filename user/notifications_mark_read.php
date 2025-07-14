<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['users'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing notification ID']);
    exit();
}

include 'includes/db_connect.php';

$user_contact = $_SESSION['user']['email'];
$notif_id = intval($data['id']);

$stmt = $conn->prepare("UPDATE notifications SET sent = 1 WHERE id = ? AND recipient_contact = ?");
$stmt->bind_param("is", $notif_id, $user_contact);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
