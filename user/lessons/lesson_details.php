<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

if (!isset($_GET['lesson_id']) || !is_numeric($_GET['lesson_id'])) {
    echo "Invalid lesson ID.";
    exit();
}

$lesson_id = intval($_GET['lesson_id']);
$email = $_SESSION['users']['email'];

// Fetch lesson details
$stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    echo "Lesson not found.";
    exit();
}

// Check if user is enrolled
$enrollment_stmt = $conn->prepare("SELECT * FROM enrollments WHERE email = ? AND lesson_id = ?");
$enrollment_stmt->bind_param("si", $email, $lesson_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();
$is_enrolled = $enrollment_result->num_rows > 0;

$payment_status = '';
if ($is_enrolled) {
    $enrollment = $enrollment_result->fetch_assoc();
    $payment_status = $enrollment['payment_status'];

    // If payment is required but not paid, redirect to payment
    if ($lesson['fee_amount'] > 0 && $payment_status != 'paid') {
        header("Location: pay_lesson.php?lesson_id=" . $lesson_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lesson['title']) ?> | Lesson Details</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2><?= htmlspecialchars($lesson['title']) ?></h2>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($lesson['description'])) ?></p>
    <p><strong>Duration:</strong> <?= htmlspecialchars($lesson['duration']) ?></p>
    <p><strong>Fee:</strong> <?= $lesson['fee_amount'] > 0 ? $lesson['fee_amount'] . ' MWK' : 'Free' ?></p>

    <?php if (!$is_enrolled): ?>
        <form action="enroll.php" method="get">
            <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
            <button type="submit" class="btn">Enroll Now</button>
        </form>
    <?php else: ?>
        <div class="lesson-materials">
            <h3>Lesson Materials</h3>
            <?php
            $materials_stmt = $conn->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ?");
            $materials_stmt->bind_param("i", $lesson_id);
            $materials_stmt->execute();
            $materials_result = $materials_stmt->get_result();

            if ($materials_result->num_rows > 0) {
                while ($material = $materials_result->fetch_assoc()) {
                    echo "<div class='material'>";
                    echo "<p><strong>" . htmlspecialchars($material['title']) . "</strong></p>";
                    echo "<a href='../uploads/" . htmlspecialchars($material['file_path']) . "' target='_blank'>Download/View</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>No materials uploaded yet.</p>";
            }
            ?>
        </div>

        <div class="lesson-quiz">
            <h3>Quiz Questions</h3>
            <?php
            $quiz_stmt = $conn->prepare("SELECT * FROM quizzes WHERE lesson_id = ?");
            $quiz_stmt->bind_param("i", $lesson_id);
            $quiz_stmt->execute();
            $quiz_result = $quiz_stmt->get_result();

            if ($quiz_result->num_rows > 0) {
                while ($quiz = $quiz_result->fetch_assoc()) {
                    echo "<div class='quiz-question'>";
                    echo "<p><strong>Q:</strong> " . htmlspecialchars($quiz['question']) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>No quiz questions available yet.</p>";
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
