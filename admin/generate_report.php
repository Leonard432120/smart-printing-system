<?php
session_start();
include '../user/includes/db_connect.php';
include 'includes/functions.php';
require '../fpdf186/fpdf.php'; // Adjust path to match your fpdf186 location

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Validate lesson_id from POST
if (!isset($_POST['lesson_id']) || !is_numeric($_POST['lesson_id'])) {
    die("Invalid lesson ID.");
}

$lesson_id = intval($_POST['lesson_id']);

// Fetch lesson details
$stmt = $conn->prepare("SELECT title, description, duration_weeks, fee_type, fee FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    die("Lesson not found.");
}

// Fetch enrollments for the lesson
$stmt = $conn->prepare("SELECT full_name, email, enrolled_at, payment_status FROM enrollments WHERE lesson_id = ? ORDER BY enrolled_at DESC");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Create PDF
class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('http://localhost/smart-printing-system/assets/images/MND.jpeg', 10, 10, 30); // Adjust path if needed
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Mungu Ni Dawa Printing & Multi-Media Services', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Mwenilondo Trading Centre, Malawi', 0, 1, 'C');
        $this->Cell(0, 5, 'Contact: +265-984-487-611 | Email: leonardponjemlungu@gmail.com', 0, 1, 'C');
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Lesson Enrollment Report', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Lesson Details
$pdf->Cell(0, 10, 'Lesson Details', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 8, 'Title:', 0);
$pdf->Cell(0, 8, $lesson['title'], 0, 1);
$pdf->Cell(40, 8, 'Description:', 0);
$pdf->MultiCell(0, 8, $lesson['description']);
$pdf->Cell(40, 8, 'Duration:', 0);
$pdf->Cell(0, 8, $lesson['duration_weeks'] . ' week(s)', 0, 1);
$pdf->Cell(40, 8, 'Fee:', 0);
$pdf->Cell(0, 8, $lesson['fee_type'] === 'Free' ? 'Free' : formatCurrency($lesson['fee']), 0, 1);
$pdf->Ln(10);

// Enrolled Students Table
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Enrolled Students', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(52, 152, 219); // #3498db
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(50, 10, 'Student Name', 1, 0, 'L', true);
$pdf->Cell(50, 10, 'Email', 1, 0, 'L', true);
$pdf->Cell(40, 10, 'Enrolled At', 1, 0, 'L', true);
$pdf->Cell(30, 10, 'Payment Status', 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

if (empty($enrollments)) {
    $pdf->Cell(0, 10, 'No students enrolled in this lesson.', 1, 1, 'C');
} else {
    foreach ($enrollments as $enrollment) {
        $pdf->Cell(50, 10, $enrollment['full_name'], 1);
        $pdf->Cell(50, 10, $enrollment['email'], 1);
        $pdf->Cell(40, 10, date('Y-m-d H:i', strtotime($enrollment['enrolled_at'])), 1);
        $pdf->Cell(30, 10, ucfirst($enrollment['payment_status']), 1, 1);
    }
}

// Summary
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Summary', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 8, 'Total Students:', 0);
$pdf->Cell(0, 8, count($enrollments), 0, 1);
$pdf->Cell(40, 8, 'Generated on:', 0);
$pdf->Cell(0, 8, date('Y-m-d H:i:s'), 0, 1);

// Output the PDF
$pdf->Output('D', "lesson_report_" . $lesson_id . "_" . date('Ymd') . ".pdf");
?>