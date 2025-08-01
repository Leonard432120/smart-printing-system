<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: land_listing.php");
    exit();
}

$land_id = intval($_POST['land_id'] ?? 0);
$user_id = $_SESSION['users']['id'];
$message = trim($_POST['message'] ?? '');

// Check if land exists
$land_stmt = $conn->prepare("SELECT id FROM lands WHERE id = ? AND status = 'available'");
$land_stmt->bind_param("i", $land_id);
$land_stmt->execute();
$land_exists = $land_stmt->get_result()->num_rows > 0;

if (!$land_exists) {
    $_SESSION['error'] = "The selected land is no longer available";
    header("Location: land_listing.php");
    exit();
}

// Check if user already expressed interest
$existing_stmt = $conn->prepare("SELECT id FROM land_requests WHERE land_id = ? AND user_id = ?");
$existing_stmt->bind_param("ii", $land_id, $user_id);
$existing_stmt->execute();
$already_requested = $existing_stmt->get_result()->num_rows > 0;

if ($already_requested) {
    $_SESSION['error'] = "You have already expressed interest in this land";
    header("Location: land_details.php?id=$land_id");
    exit();
}

// Insert new interest
$insert_stmt = $conn->prepare("INSERT INTO land_requests (land_id, user_id, message) VALUES (?, ?, ?)");
$insert_stmt->bind_param("iis", $land_id, $user_id, $message);
$insert_stmt->execute();

if ($insert_stmt->affected_rows > 0) {
    $_SESSION['success'] = "Your interest has been submitted successfully! We'll contact you soon.";
} else {
    $_SESSION['error'] = "Failed to submit your interest. Please try again.";
}

header("Location: land_details.php?id=$land_id");
exit();
?>