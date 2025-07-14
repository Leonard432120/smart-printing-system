<?php
session_start();
include '../includes/db_connect.php';

// Redirect if user not logged in
if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

// Validate lesson_id param
if (!isset($_GET['lesson_id']) || !is_numeric($_GET['lesson_id'])) {
    echo "Invalid lesson ID.";
    exit();
}

$lesson_id = intval($_GET['lesson_id']);
$email = $_SESSION['users']['email'];
$full_name = $_SESSION['users']['name'] ?? 'Unknown User';

// Check if already enrolled
$check = $conn->prepare("SELECT * FROM enrollments WHERE email = ? AND lesson_id = ?");
$check->bind_param("si", $email, $lesson_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Already enrolled, redirect to lesson details with message
    header("Location: lesson_details.php?lesson_id=$lesson_id&status=already_enrolled");
    exit();
}

// Get lesson details
$stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    echo "Lesson not found.";
    exit();
}

// Check fee_type for free or paid lesson
// Assuming fee_type is stored as 'Free' or 'Paid' in the DB as string
if (strtolower($lesson['fee_type']) === 'free') {
    // Free lesson: enroll immediately
    $payment_status = 'Paid'; // mark as paid since no payment needed

    $insert = $conn->prepare("INSERT INTO enrollments (full_name, email, lesson_id, enrolled_at, payment_status) VALUES (?, ?, ?, NOW(), ?)");
    $insert->bind_param("ssis", $full_name, $email, $lesson_id, $payment_status);
    if ($insert->execute()) {
        header("Location: lesson_details.php?lesson_id=$lesson_id&status=free_enrolled");
        exit();
    } else {
        echo "Error enrolling in free lesson: " . $conn->error;
        exit();
    }
} else {
    // Paid lesson: redirect to payment page
    header("Location: pay_lesson.php?lesson_id=$lesson_id");
    exit();
}
