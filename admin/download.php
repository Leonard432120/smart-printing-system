<?php
// download.php
require('C:/wamp64/www/smart-printing-system/fpdf186/fpdf.php');
include '../user/includes/db_connect.php';

// Verify admin access
session_start();
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    die("Access denied");
}

// Get file ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch file info
$stmt = $conn->prepare("SELECT file_name, original_filename FROM transactions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found in database");
}

$file = $result->fetch_assoc();
$filename = basename($file['file_name']);
$server_path = 'C:/wamp64/www/smart-printing-system/uploads/notes/' . $filename;
$download_name = $file['original_filename'] ?: $filename;

// Verify file exists
if (!file_exists($server_path)) {
    die("File not found on server");
}

// Create PDF wrapper
$pdf = new FPDF();
$pdf->AddPage();

// Add file content to PDF
if (strtolower(pathinfo($server_path, PATHINFO_EXTENSION)) === 'pdf') {
    // For PDF files, just output directly
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    readfile($server_path);
} else {
    // For other files, create a PDF wrapper
    $content = file_get_contents($server_path);
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(40,10,'Original File: ' . $download_name);
    $pdf->Ln();
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,10, 'File content: ' . base64_encode($content));
    
    // Output PDF
    $pdf->Output('D', $download_name . '.pdf'); // 'D' forces download
}