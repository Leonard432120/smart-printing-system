<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: ../login.php");
    exit();
}

$resource_id = intval($_GET['id'] ?? 0);

// Fetch resource details
$stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$resource = $stmt->get_result()->fetch_assoc();

if (!$resource) {
    die("Resource not found");
}

// Set appropriate headers and output the file
header('Content-Type: ' . mime_content_type($resource['file_path']));
header('Content-Disposition: inline; filename="' . basename($resource['file_path']) . '"');
readfile($resource['file_path']);
exit();
?>