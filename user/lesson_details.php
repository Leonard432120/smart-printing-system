<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

if (!isset($_GET['lesson_id']) || !is_numeric($_GET['lesson_id'])) {
    echo "Invalid lesson ID.";
    exit();
}

$lesson_id = intval($_GET['lesson_id']);
$email = $_SESSION['users']['email'] ?? '';

// Fetch lesson details
$stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    echo "Lesson not found.";
    exit();
}

// Check enrollment
$enroll_stmt = $conn->prepare("SELECT * FROM enrollments WHERE email = ? AND lesson_id = ?");
$enroll_stmt->bind_param("si", $email, $lesson_id);
$enroll_stmt->execute();
$enroll_result = $enroll_stmt->get_result();
$is_enrolled = $enroll_result->num_rows > 0;

$payment_status = '';
if ($is_enrolled) {
    $enrollment = $enroll_result->fetch_assoc();
    $payment_status = strtolower($enrollment['status']);

    if ($lesson['fee_type'] === 'Paid' && $lesson['fee'] > 0 && $payment_status !== 'paid') {
        header("Location: pay_lesson.php?lesson_id=" . $lesson_id);
        exit();
    }
}

// Fetch upcoming classes for this lesson
$upcomingClasses = [];
$class_stmt = $conn->prepare("
    SELECT sc.id, sc.topic, sc.class_date, sc.class_link 
    FROM scheduled_classes sc 
    WHERE sc.lesson_id = ? AND sc.class_date >= NOW() 
    ORDER BY sc.class_date ASC
");
$class_stmt->bind_param("i", $lesson_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
while ($row = $class_result->fetch_assoc()) {
    $upcomingClasses[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($lesson['title'] ?? 'Lesson') ?> | Lesson Details</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
    <style>
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .btn {
            background-color: #3498db;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .section-title {
            font-size: 1.4rem;
            margin: 30px 0 15px;
            color: #333;
        }
        .materials-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }
        .material-card {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .material-thumb img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .material-content h4 {
            font-size: 1.2rem;
            margin: 0 0 10px;
            color: #0a3d62;
        }
        .material-content p {
            margin: 0 0 12px;
            color: #555;
            font-size: 0.95rem;
        }
        .download-link {
            display: inline-block;
            background-color: #0a3d62;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .download-link:hover {
            background-color: #064173;
        }
        .quiz-question {
            background: #f9f9f9;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
        .upcoming-classes {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f4f6f9;
        }
        .upcoming-classes ul {
            list-style: none;
            padding-left: 0;
        }
        .upcoming-classes li {
            margin-bottom: 12px;
            font-size: 1rem;
        }
        .live-badge {
            background-color: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        @media (max-width: 600px) {
            .material-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h2><?= htmlspecialchars($lesson['title']) ?></h2>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($lesson['description'] ?? 'No description')) ?></p>

    <?php if (!$is_enrolled): ?>
        <form action="enroll.php" method="get">
            <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
            <button type="submit" class="btn">Enroll Now</button>
        </form>
    <?php else: ?>

        <?php if (count($upcomingClasses) > 0): ?>
            <div class="upcoming-classes">
                <strong>Upcoming Classes for this Lesson:</strong>
                <ul>
                    <?php
                    foreach ($upcomingClasses as $class):
                        $classTimestamp = strtotime($class['class_date']);
                        $nowTimestamp = time();
                        $diffSeconds = $classTimestamp - $nowTimestamp;

                        // Determine if class is live or within 10 minutes
                        $isLive = ($nowTimestamp >= $classTimestamp && $nowTimestamp <= ($classTimestamp + 3600));
                        $showLink = ($diffSeconds <= 600 && $diffSeconds >= 0) || $isLive;

                        $classDateTimeFormatted = date('D, M j, Y \a\t g:i A', $classTimestamp);
                        ?>
                        <li>
                            <strong><?= htmlspecialchars($class['topic']) ?></strong> — <?= $classDateTimeFormatted ?>
                            <?php if ($isLive): ?>
                                <span class="live-badge">LIVE NOW</span>
                            <?php endif; ?>
                            <?php if ($showLink): ?>
                                <a href="<?= htmlspecialchars($class['class_link']) ?>" target="_blank">Join Class</a>
                            <?php else: ?>
                                <em>Class will be at this time</em>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <small style="color:#7f8c8d;">Make sure to join the class on time!</small>
            </div>
        <?php else: ?>
            <p>No upcoming classes scheduled.</p>
        <?php endif; ?>

        <h3 class="section-title">📄 Lesson Materials</h3>
        <?php
        $materials_stmt = $conn->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ?");
        $materials_stmt->bind_param("i", $lesson_id);
        $materials_stmt->execute();
        $materials_result = $materials_stmt->get_result();

        if ($materials_result->num_rows > 0): ?>
            <div class="materials-grid">
                <?php while ($material = $materials_result->fetch_assoc()): ?>
                    <article class="material-card">
                        <div class="material-thumb">
                            <?php
                            $thumbPath = !empty($material['thumbnail']) 
                                ? 'assets/images/' . htmlspecialchars($material['thumbnail']) 
                                : 'assets/images/thumb_1752734645.png';
                            ?>
                            <img src="/smart-printing-system/<?= $thumbPath ?>" alt="Note Image" onerror="this.src='/smart-printing-system/assets/images/thumb_1752734645.png';" />
                        </div>
                        <div class="material-content">
                            <h4><?= htmlspecialchars($material['title'] ?? 'Untitled') ?></h4>
                            <p><?= nl2br(htmlspecialchars($material['description'] ?? '')) ?></p>
                            <?php if (!empty($material['file_path'])): ?>
                                <a href="/smart-printing-system/uploads/notes/<?= htmlspecialchars($material['file_path']) ?>" class="download-link" target="_blank" download>
                                    📥 Download
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No materials uploaded yet.</p>
        <?php endif; ?>

        <h3 class="section-title">🧠 Quiz Questions</h3>
        <?php
        $quiz_stmt = $conn->prepare("SELECT * FROM quizzes WHERE lesson_id = ?");
        $quiz_stmt->bind_param("i", $lesson_id);
        $quiz_stmt->execute();
        $quiz_result = $quiz_stmt->get_result();

        if ($quiz_result->num_rows > 0):
            while ($quiz = $quiz_result->fetch_assoc()): ?>
                <div class="quiz-question">
                    <p><strong>Q:</strong> <?= htmlspecialchars($quiz['question']) ?></p>
                </div>
            <?php endwhile;
        else:
            echo "<p>No quiz questions available yet.</p>";
        endif;
        ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>