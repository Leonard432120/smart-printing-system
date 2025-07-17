<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

session_start();
include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

function sendNotificationEmailPHPMailer($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ict-01-25-22@unilia.ac.mw';
        $mail->Password = 'ifid zzgh jhik oync';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ict-01-25-22@unilia.ac.mw', 'Mungu Ni Dawa shop');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_lesson') {
            $title = strip_tags(trim($_POST['title']));
            $description = strip_tags(trim($_POST['description']));
            $duration_weeks = filter_input(INPUT_POST, 'duration_weeks', FILTER_SANITIZE_NUMBER_INT);
            $fee_type = strip_tags(trim($_POST['fee_type']));
            $fee = ($fee_type === 'Free') ? 0 : filter_input(INPUT_POST, 'fee', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $instructor = strip_tags(trim($_POST['instructor']));
            $schedule = strip_tags(trim($_POST['schedule']));

            $stmt = $conn->prepare("INSERT INTO lessons (title, description, duration_weeks, fee_type, fee, instructor, schedule, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssisdss", $title, $description, $duration_weeks, $fee_type, $fee, $instructor, $schedule);
            $statusMsg = $stmt->execute() ? "Lesson added successfully!" : "Error adding lesson: " . $conn->error;

        } elseif ($_POST['action'] === 'enroll_student') {
            $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_SANITIZE_NUMBER_INT);
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
            $payment_status = ucfirst(strip_tags(trim($_POST['payment_status'])));

            $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                $statusMsg = "Selected user not found.";
            } else {
                $full_name = $user['name'];
                $email = $user['email'];
                $user_role = strtolower($user['role']);

                $stmt = $conn->prepare("SELECT id FROM enrollments WHERE email = ? AND lesson_id = ?");
                $stmt->bind_param("si", $email, $lesson_id);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();

                if ($exists) {
                    $statusMsg = "Student is already enrolled in this lesson.";
                } else {
                    $stmt = $conn->prepare("SELECT title, fee_type FROM lessons WHERE id = ?");
                    $stmt->bind_param("i", $lesson_id);
                    $stmt->execute();
                    $lesson = $stmt->get_result()->fetch_assoc();

                    if ($lesson && $lesson['fee_type'] === 'Paid' && strtolower($payment_status) !== 'Paid') {
                        if ($user_role === 'admin' || $user_role === 'staff') {
                            $statusMsg = "This is a paid lesson. Confirm payment before enrolling.";
                        } else {
                            echo "<script>
                                if (!confirm('This is a paid lesson but payment is marked as " . $payment_status . ". Do you still want to proceed with enrollment?')) {
                                    window.history.back();
                                }
                            </script>";
                        }
                    }

                    if (!isset($statusMsg)) {
                        $stmt = $conn->prepare("INSERT INTO enrollments (full_name, email, lesson_id, enrolled_at, status, progress, completion_status) VALUES (?, ?, ?, NOW(), ?, 0, 'Not Started')");
                        $stmt->bind_param("ssis", $full_name, $email, $lesson_id, $payment_status);

                        if ($stmt->execute()) {
                            $statusMsg = "Student enrolled successfully!";

                            $subject = "Lesson Enrollment Confirmation - Smart Printing";
                            $message = "<html><head><title>Enrollment Confirmation</title></head><body style='font-family: Arial, sans-serif;'>
                                <h2>Hello {$full_name},</h2>
                                <p>You have been successfully enrolled in the lesson: <strong>{$lesson['title']}</strong>.</p>
                                <p>You can now access the learning materials and begin your course.</p>
                                <p>Thank you,<br><strong>Smart Printing Team</strong></p>
                                </body></html>";

                            $emailSent = sendNotificationEmailPHPMailer($email, $full_name, $subject, $message);

                            if (!$emailSent) {
                                $statusMsg .= " (However, email notification failed to send.)";
                            }
                        } else {
                            $statusMsg = "Error enrolling student: " . $conn->error;
                        }
                    }
                }
            }

        } elseif ($_POST['action'] === 'delete_lesson') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
            $stmt->bind_param("i", $id);
            $statusMsg = $stmt->execute() ? "Lesson deleted successfully!" : "Error deleting lesson: " . $conn->error;

        } elseif ($_POST['action'] === 'update_progress') {
            $enrollment_id = filter_input(INPUT_POST, 'enrollment_id', FILTER_SANITIZE_NUMBER_INT);
            $progress = filter_input(INPUT_POST, 'progress', FILTER_SANITIZE_NUMBER_INT);
            $completion_status = strip_tags(trim($_POST['completion_status']));

            $stmt = $conn->prepare("UPDATE enrollments SET progress = ?, completion_status = ? WHERE id = ?");
            $stmt->bind_param("isi", $progress, $completion_status, $enrollment_id);
            $statusMsg = $stmt->execute() ? "Student progress updated successfully!" : "Error updating progress: " . $conn->error;
        }
    }
}

// Fetch lessons
$lessons = [];
$result = $conn->query("SELECT * FROM lessons ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $lessons[] = $row;
}

// Fetch enrollments for each lesson
$enrollments = [];
foreach ($lessons as $lesson) {
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE lesson_id = ?");
    $stmt->bind_param("i", $lesson['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollments[$lesson['id']] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Fetch students
$students = [];
$result = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Helper function to format currency - avoid redeclaration if exists
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return 'MWK ' . number_format($amount, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Lessons | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
    /* ==== General Reset ==== */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* ==== Table Styles ==== */
    .lesson-table, .enrollment-table {
      width: 100%;
      border-collapse: collapse;
      margin: 30px 0;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .lesson-table th, .lesson-table td,
    .enrollment-table th, .enrollment-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e6e6e6;
      font-size: 0.95rem;
      color: #333;
    }

    .lesson-table th, .enrollment-table th {
      background: #0a3d62;
      color: #ffffff;
      font-weight: 600;
      font-size: 1rem;
    }

    /* Zebra rows */
    .lesson-table tr:nth-child(even),
    .enrollment-table tr:nth-child(even) {
      background: #f9fbfd;
    }

    /* ==== Form Container ==== */
    .form-container {
      background: #ffffff;
      padding: 30px 25px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
      max-width: 100%;
    }

    .form-container h2 {
      margin-bottom: 20px;
      color: #0a3d62;
      font-size: 1.6rem;
      font-weight: 600;
      text-align: center;
    }

    /* ==== Inputs, Textareas, Selects ==== */
    .form-container input,
    .form-container select,
    .form-container textarea {
      width: 100%;
      padding: 12px 15px;
      margin: 10px 0;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      font-family: inherit;
      background-color: #fdfdfd;
    }

    .form-container input:focus,
    .form-container textarea:focus,
    .form-container select:focus {
      border-color: #0a3d62;
      outline: none;
      box-shadow: 0 0 0 2px rgba(10, 61, 98, 0.1);
    }

    .form-container select option {
      padding: 10px;
    }

    /* ==== Submit Button ==== */
    .form-container button {
      width: 100%;
      padding: 12px;
      background-color: #0a3d62;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: background-color 0.3s ease;
    }

    .form-container button:hover {
      background-color: #064173;
    }

    /* ==== Label Styling ==== */
    .form-container label {
      display: block;
      margin-top: 10px;
      font-weight: 500;
      color: #333;
    }

    /* ==== Status Messages ==== */
    .status-msg {
      padding: 12px 18px;
      margin-bottom: 20px;
      border-radius: 6px;
      text-align: center;
      font-size: 1rem;
      font-weight: 500;
    }

    .status-msg.success {
      background: #2ecc71;
      color: #ffffff;
    }

    .status-msg.error {
      background: #e74c3c;
      color: #ffffff;
    }

    /* ==== Action Links ==== */
    .action-links {
      display: flex;
      gap: 10px;
    }

    .action-links a {
      color: #0a3d62;
      font-weight: 500;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .action-links a:hover {
      text-decoration: underline;
    }

    .action-links a.delete {
      color: #e74c3c;
    }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar (same as dashboard) -->
        <div class="sidebar">
            <div class="logo">
                <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img" />
                <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php" class="active"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-book"></i> Manage Lessons</h1>
                    <p>Manage computer lessons and student enrollments for Mungu Ni Dawa.</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <?php if (isset($statusMsg)): ?>
                <div class="status-msg <?php echo strpos(strtolower($statusMsg), 'success') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($statusMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Add Lesson Form -->
            <div class="form-container">
                <h2>Add New Lesson</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_lesson" />
                    <input type="text" name="title" placeholder="Lesson Title" required />
                    <textarea name="description" placeholder="Description" required></textarea>
                    <input type="number" name="duration_weeks" placeholder="Duration (Weeks)" required min="1" />
                    <select name="fee_type" required onchange="this.form.fee.disabled = this.value === 'Free'">
                        <option value="Free">Free</option>
                        <option value="Paid">Paid</option>
                    </select>
                    <input type="number" name="fee" placeholder="Fee (MWK)" step="0.01" min="0" disabled />
                    <input type="text" name="instructor" placeholder="Instructor Name" required />
                    <input type="text" name="schedule" placeholder="Schedule (e.g., Mon/Wed 10-12)" required />
                    <button type="submit">Add Lesson</button>
                </form>
            </div>

            <!-- Lessons List -->
            <?php foreach ($lessons as $lesson): ?>
                <table class="lesson-table">
                    <thead>
                        <tr>
                            <th colspan="6">
                                <?php echo htmlspecialchars($lesson['title']); ?>
                                <form method="POST" style="float: right;">
                                    <input type="hidden" name="id" value="<?php echo $lesson['id']; ?>" />
                                    <input type="hidden" name="action" value="delete_lesson" />
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this lesson?');" style="background:none;border:none;color:#e74c3c;cursor:pointer;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="6"><strong>Description:</strong> <?php echo htmlspecialchars($lesson['description']); ?></td></tr>
                        <tr><td><strong>Duration (weeks):</strong> <?php echo (int)$lesson['duration_weeks']; ?></td>
                            <td><strong>Fee Type:</strong> <?php echo htmlspecialchars($lesson['fee_type']); ?></td>
                            <td><strong>Fee:</strong> <?php echo formatCurrency($lesson['fee']); ?></td>
                            <td><strong>Instructor:</strong> <?php echo htmlspecialchars($lesson['instructor']); ?></td>
                            <td><strong>Schedule:</strong> <?php echo htmlspecialchars($lesson['schedule']); ?></td>
                            <td></td>
                        </tr>
                        <tr><td colspan="6">
                            <!-- Enroll Student Form -->
                            <form method="POST" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center;">
                                <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>" />
                                <input type="hidden" name="action" value="enroll_student" />
                                <select name="user_id" required>
                                    <option value="">Select student to enroll</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="payment_status" required>
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Free">Free</option>
                                </select>
                                <button type="submit" style="background:#27ae60;color:#fff;padding:8px 15px;border:none;border-radius:6px;cursor:pointer;">Enroll</button>
                            </form>
                        </td></tr>

                        <!-- Enrollment List -->
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Enrolled At</th>
                            <th>Progress (%)</th>
                            <th>Completion Status</th>
                            <th>Actions</th>
                        </tr>
                        <?php if (!empty($enrollments[$lesson['id']])): ?>
                            <?php foreach ($enrollments[$lesson['id']] as $enroll): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($enroll['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enroll['email']); ?></td>
                                    <td><?php echo htmlspecialchars($enroll['enrolled_at']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block; min-width: 120px;">
                                            <input type="hidden" name="action" value="update_progress" />
                                            <input type="hidden" name="enrollment_id" value="<?php echo $enroll['id']; ?>" />
                                            <input type="number" name="progress" value="<?php echo (int)$enroll['progress']; ?>" min="0" max="100" style="width: 50px;" required />%
                                            <select name="completion_status" required>
                                                <option value="Not Started" <?php if ($enroll['completion_status'] === 'Not Started') echo 'selected'; ?>>Not Started</option>
                                                <option value="In Progress" <?php if ($enroll['completion_status'] === 'In Progress') echo 'selected'; ?>>In Progress</option>
                                                <option value="Completed" <?php if ($enroll['completion_status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                                            </select>
                                            <button type="submit" style="padding:4px 8px; background:#2980b9; color:#fff; border:none; border-radius:4px; cursor:pointer;">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo htmlspecialchars($enroll['completion_status']); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this enrollment?');" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete_enrollment" />
                                            <input type="hidden" name="id" value="<?php echo $enroll['id']; ?>" />
                                            <button type="submit" style="background:none;border:none;color:#e74c3c;cursor:pointer;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; color:#999;">No enrollments yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
