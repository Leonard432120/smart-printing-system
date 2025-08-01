<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

$lesson_id = intval($_GET['lesson_id']);
$email = $_SESSION['users']['email'];

// Check enrollment status FIRST
$enrollment = $conn->prepare("SELECT * FROM enrollments WHERE email = ? AND lesson_id = ?");
$enrollment->bind_param("si", $email, $lesson_id);
$enrollment->execute();
$enrollment_result = $enrollment->get_result();

// If already enrolled (by admin or otherwise), go to lesson
if ($enrollment_result->num_rows > 0) {
    header("Location: lesson_details.php?lesson_id=$lesson_id");
    exit();
}

// Get lesson details
$lesson = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$lesson->bind_param("i", $lesson_id);
$lesson->execute();
$lesson_result = $lesson->get_result()->fetch_assoc();

if (!$lesson_result) {
    die("Lesson not found");
}

// Handle based on fee type
if (strtolower($lesson_result['fee_type']) === 'free') {
    // Insert free enrollment
    $insert = $conn->prepare("INSERT INTO enrollments (email, lesson_id, status, enrolled_at) VALUES (?, ?, 'Paid', NOW())");
    $insert->bind_param("si", $email, $lesson_id);
    $insert->execute();
    header("Location: lesson_details.php?lesson_id=$lesson_id");
} else {
    // For paid lessons, check if admin already marked as paid
    if (isset($_SESSION['users']['role']) && in_array($_SESSION['users']['role'], ['admin', 'staff'])) {
        // Admin/staff can bypass payment
        $insert = $conn->prepare("INSERT INTO enrollments (email, lesson_id, status, enrolled_at) VALUES (?, ?, 'Paid', NOW())");
        $insert->bind_param("si", $email, $lesson_id);
        $insert->execute();
        header("Location: lesson_details.php?lesson_id=$lesson_id");
    } else {
        // Regular users go to payment
        header("Location: pay_lesson.php?lesson_id=$lesson_id");
    }
}
exit();