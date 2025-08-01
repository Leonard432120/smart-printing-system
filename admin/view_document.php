<?php
// view_document.php
require __DIR__ . '/../vendor/autoload.php';
include '../user/includes/db_connect.php';
include 'includes/functions.php';

// Restrict access to admin only
session_start();
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (!isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$transaction_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT file_name, original_filename FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if (!$file) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$filepath = 'C:/wamp64/www/smart-printing-system/uploads/notes/' . basename($file['file_name']);

if (!file_exists($filepath)) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

// Get file extension and set appropriate Content-Type
$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'txt' => 'text/plain',
    'csv' => 'text/csv',
    // Add more as needed
];

$content_type = $mime_types[$extension] ?? 'application/octet-stream';

header("Content-Type: $content_type");
header("Content-Disposition: inline; filename=\"" . basename($file['original_filename']) . "\"");
header("Content-Length: " . filesize($filepath));

readfile($filepath);
exit();
?>