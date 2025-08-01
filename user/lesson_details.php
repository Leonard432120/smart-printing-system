<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Africa/Nairobi');

session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

$lesson_id = intval($_GET['lesson_id'] ?? 0);
$email = $_SESSION['users']['email'] ?? 0;
$user_role = strtolower($_SESSION['users']['role'] ?? '');
$user_id = $_SESSION['users']['id'] ?? 0;

// Fetch lesson details
$lesson = [];
if ($lesson_id) {
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
}

if (!$lesson) {
    echo "Lesson not found.";
    exit();
}

// Check enrollment
$is_enrolled = false;
if ($email) {
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE email = ? AND lesson_id = ?");
    $stmt->bind_param("si", $email, $lesson_id);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $is_enrolled = !empty($enrollment);
}

// Fetch classes for this lesson
$upcomingClasses = [];
$stmt = $conn->prepare("SELECT sc.* FROM scheduled_classes sc WHERE sc.lesson_id = ? ORDER BY sc.class_date ASC");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$class_result = $stmt->get_result();
while ($row = $class_result->fetch_assoc()) {
    $upcomingClasses[] = $row;
}

// Fetch lesson materials
$materials = [];
$materials_stmt = $conn->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ?");
$materials_stmt->bind_param("i", $lesson_id);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
while ($material = $materials_result->fetch_assoc()) {
    $materials[] = $material;
}

// Fetch quiz questions
$quizzes = [];
$quiz_stmt = $conn->prepare("SELECT * FROM quizzes WHERE lesson_id = ?");
$quiz_stmt->bind_param("i", $lesson_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
while ($quiz = $quiz_result->fetch_assoc()) {
    $quizzes[] = $quiz;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($lesson['title'] ?? 'Lesson') ?> | Lesson Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
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
        display: inline-block;
    }
    
    .btn:hover {
        background-color: #2980b9;
    }
    
    .section-title {
        font-size: 1.4rem;
        margin: 30px 0 15px;
        color: #333;
    }
    
    .upcoming-classes {
        margin-top: 30px;
        padding: 20px 25px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background-color: #f9fbfd;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    
    .upcoming-classes strong {
        color: #2c3e50;
        font-weight: 600;
    }
    
    .upcoming-classes ul {
        list-style: none;
        padding-left: 0;
        margin-top: 15px;
    }
    
    .upcoming-classes li {
        margin-bottom: 20px;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .class-info {
        margin-bottom: 15px;
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
    
    .time-info {
        color: #7f8c8d;
        font-style: italic;
        font-size: 0.9rem;
    }
    
    .discussion-link {
        display: inline-block;
        margin-top: 10px;
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
                <strong>Class Schedule for this Lesson:</strong>
                <ul>
                    <?php foreach ($upcomingClasses as $class):
                        $currentTime = time();
                        $classTime = strtotime($class['class_date']);
                        $tenMinutesBefore = $classTime - (10 * 60);
                        $oneHourAfter = $classTime + (60 * 60);
                        
                        $isPast = ($currentTime > $oneHourAfter);
                        $isUpcoming = ($currentTime < $tenMinutesBefore);
                        $isSoon = ($currentTime >= $tenMinutesBefore && $currentTime < $classTime);
                        $isActive = ($currentTime >= $classTime && $currentTime <= $oneHourAfter);
                        
                        $classDateTimeFormatted = date('D, M j, Y \a\t g:i A', $classTime);
                    ?>
                    <li>
                        <div class="class-info">
                            <strong><?= htmlspecialchars($class['topic']) ?></strong> — <?= $classDateTimeFormatted ?>
                            <?php if ($isActive): ?>
                                <span class="live-badge">LIVE NOW</span>
                                <a href="<?= htmlspecialchars($class['class_link']) ?>" target="_blank" class="btn">Join Class</a>
                            <?php elseif ($isSoon): ?>
                                <a href="<?= htmlspecialchars($class['class_link']) ?>" target="_blank" class="btn">Join Class</a>
                                <span class="time-info">(Class starting soon)</span>
                            <?php elseif ($isUpcoming): ?>
                                <span class="time-info">(Starts in <?= ceil(($classTime - $currentTime)/60) ?> minutes)</span>
                            <?php else: ?>
                                <span class="time-info">(Class ended)</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="discussion-link">
                            <a href="class_discussion.php?class_id=<?= $class['id'] ?>&lesson_id=<?= $lesson_id ?>" class="btn">
                                <i class="fas fa-comments"></i> View Discussions
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No classes scheduled for this lesson.</p>
        <?php endif; ?>

        <!-- Lesson Materials Section -->
        <h3 class="section-title">📄 Lesson Materials</h3>
        <?php if (count($materials) > 0): ?>
            <div class="materials-grid">
                <?php foreach ($materials as $material): ?>
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
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No materials uploaded yet.</p>
        <?php endif; ?>

        <!-- Quiz Section -->
        <h3 class="section-title">🧠 Quiz Questions</h3>
        <?php if (count($quizzes) > 0): ?>
            <?php foreach ($quizzes as $quiz): ?>
                <div class="quiz-question">
                    <p><strong>Q:</strong> <?= htmlspecialchars($quiz['question']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No quiz questions available yet.</p>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>