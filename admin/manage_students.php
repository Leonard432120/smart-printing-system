<?php
// manage_students.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Start session and includes
session_start();
include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// === Helper function: Send email notification ===
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

// === Helper function: Log audit trail ===
function logAuditTrail($conn, $user, $action, $description) {
    $stmt = $conn->prepare("INSERT INTO audit_trails (user, action, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $action, $description);
    $stmt->execute();
}

// === Module 2: Form Processing ===
$statusMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUser = $_SESSION['users']['username'] ?? 'unknown';

    if (isset($_POST['action'])) {
        // Update Enrollment
        if ($_POST['action'] === 'update_enrollment') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            $payment_status = filter_var($_POST['payment_status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $validStatuses = ['Paid', 'Pending', 'Failed'];
            if (!$email) {
                $statusMsg = "Invalid email format.";
            } elseif (!in_array($payment_status, $validStatuses)) {
                $statusMsg = "Invalid payment status selected.";
            } elseif (empty($full_name)) {
                $statusMsg = "Full name is required.";
            } else {
                $stmt = $conn->prepare("UPDATE enrollments SET full_name = ?, email = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $email, $payment_status, $id);
                if ($stmt->execute()) {
                    $statusMsg = "Enrollment updated successfully!";

                    // Log audit trail
                    logAuditTrail($conn, $adminUser, "Update Enrollment", "Updated enrollment ID $id: Full Name=$full_name, Email=$email, Payment Status=$payment_status");

                    // Send notification email
                    $subject = "Your Enrollment Details Have Been Updated";
                    $message = "
                        <p>Dear " . htmlspecialchars($full_name) . ",</p>
                        <p>Your enrollment details for the lesson have been updated successfully.</p>
                        <p>If you did not request this change, please contact us immediately.</p>
                        <p>Thank you,<br/>Mungu Ni Dawa Team</p>
                    ";
                    sendNotificationEmailPHPMailer($email, $full_name, $subject, $message);

                    // Insert notification record for user
                    $notifMsg = "Your enrollment details were updated by admin.";
                    $stmtNotif = $conn->prepare("INSERT INTO notifications (type, recipient_email, message, is_read) VALUES (?, ?, ?, 0)");
                    $notifType = "Enrollment Update";
                    $stmtNotif->bind_param("sss", $notifType, $email, $notifMsg);
                    $stmtNotif->execute();

                } else {
                    $statusMsg = "Error updating enrollment: " . $conn->error;
                }
            }
        }
        // Delete Enrollment
        elseif ($_POST['action'] === 'delete_enrollment') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            // Get enrollment info before deleting to notify
            $stmtGet = $conn->prepare("SELECT full_name, email FROM enrollments WHERE id = ?");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $resultGet = $stmtGet->get_result();
            $enrollmentInfo = $resultGet->fetch_assoc();

            $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $statusMsg = "Enrollment deleted successfully!";

                // Log audit trail
                logAuditTrail($conn, $adminUser, "Delete Enrollment", "Deleted enrollment ID $id: Full Name={$enrollmentInfo['full_name']}, Email={$enrollmentInfo['email']}");

                // Send notification email
                $subject = "Your Enrollment Has Been Deleted";
                $message = "
                    <p>Dear " . htmlspecialchars($enrollmentInfo['full_name']) . ",</p>
                    <p>Your enrollment for the lesson has been deleted from our system.</p>
                    <p>If you did not request this deletion, please contact us immediately.</p>
                    <p>Thank you,<br/>Mungu Ni Dawa Team</p>
                ";
                sendNotificationEmailPHPMailer($enrollmentInfo['email'], $enrollmentInfo['full_name'], $subject, $message);

                // Insert notification record for user
                $notifMsg = "Your enrollment was deleted by admin.";
                $stmtNotif = $conn->prepare("INSERT INTO notifications (type, recipient_email, message, is_read) VALUES (?, ?, ?, 0)");
                $notifType = "Enrollment Deletion";
                $stmtNotif->bind_param("sss", $notifType, $enrollmentInfo['email'], $notifMsg);
                $stmtNotif->execute();
            } else {
                $statusMsg = "Error deleting enrollment: " . $conn->error;
            }
        }
        // Attendance Marking
        elseif ($_POST['action'] === 'mark_attendance') {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT);
            $attendance_status = $_POST['attendance_status'] ?? 'Absent'; // Default Absent

            if (!in_array($attendance_status, ['Present', 'Absent'])) {
                $statusMsg = "Invalid attendance status.";
            } else {
                $attendance_date = date('Y-m-d');

                // Check if record exists for today
                $stmtCheck = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND lesson_id = ? AND DATE(attended_at) = ?");
                $stmtCheck->bind_param("iis", $user_id, $lesson_id, $attendance_date);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();

                if ($resultCheck->num_rows > 0) {
                    // Update existing record
                    $attendanceId = $resultCheck->fetch_assoc()['id'];
                    $stmtUpd = $conn->prepare("UPDATE attendance SET status = ?, attended_at = NOW() WHERE id = ?");
                    $stmtUpd->bind_param("si", $attendance_status, $attendanceId);
                    $stmtUpd->execute();
                } else {
                    // Insert new record
                    $stmtIns = $conn->prepare("INSERT INTO attendance (user_id, lesson_id, attended_at, status) VALUES (?, ?, NOW(), ?)");
                    $stmtIns->bind_param("iis", $user_id, $lesson_id, $attendance_status);
                    $stmtIns->execute();
                }
                $statusMsg = "Attendance marked as $attendance_status.";
            }
        }
    }
}

// === Module 3: Fetch Enrollments ===
$enrollments = [];
$result = $conn->query("
    SELECT e.id, u.name AS full_name, e.email, e.lesson_id, e.status AS payment_status, e.enrolled_at,
       l.title AS lesson_title
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN lessons l ON e.lesson_id = l.id
    ORDER BY e.enrolled_at DESC
");
while ($row = $result->fetch_assoc()) {
    $enrollments[] = $row;
}

// === Module 4: Fetch Notifications for admin dashboard ===
$notifications = [];
$notifResult = $conn->query("SELECT id, type, recipient_email, message, is_read, created_at FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 10");
while ($notif = $notifResult->fetch_assoc()) {
    $notifications[] = $notif;
}

// === Module 5: Fetch Attendance Records for display ===
$attendanceRecords = [];
$attResult = $conn->query("
    SELECT a.id, a.user_id, a.lesson_id, DATE(a.attended_at) AS attendance_date, a.status,
           u.name AS full_name, l.title
    FROM attendance a 
    JOIN users u ON a.user_id = u.id
    JOIN lessons l ON a.lesson_id = l.id
    ORDER BY a.attended_at DESC, u.name ASC
");
while ($att = $attResult->fetch_assoc()) {
    $attendanceRecords[] = $att;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Students | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        /* Styles */
        .enrollment-table, .attendance-table, .notifications-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .enrollment-table th, .enrollment-table td,
        .attendance-table th, .attendance-table td,
        .notifications-table th, .notifications-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .enrollment-table th, .attendance-table th, .notifications-table th {
            background: #3498db;
            color: #fff;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container button {
            padding: 10px 20px;
            background: #3498db;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }
        .form-container button:hover {
            background: #2980b9;
        }
        .status-msg {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .status-msg.success {
            background: #2ecc71;
            color: #fff;
        }
        .status-msg.error {
            background: #e74c3c;
            color: #fff;
        }
        .action-links a {
            margin-right: 10px;
            color: #3498db;
            text-decoration: none;
        }
        .action-links a.delete {
            color: #e74c3c;
        }
        /* Edit form container inside table cell */
.form-container {
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  max-width: 600px;
  margin: 10px auto;
  box-sizing: border-box;
}

/* Make form inputs full width */
.form-container input[type="text"],
.form-container input[type="email"],
.form-container select {
  width: 100%;
  padding: 10px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
}

/* Buttons side by side on desktop, stacked on mobile */
.form-container button {
  padding: 10px 20px;
  font-weight: bold;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  margin-right: 10px;
}

.form-container button[type="submit"] {
  background-color: #3498db;
  color: white;
}

.form-container button[type="button"] {
  background-color: #ccc;
  color: #333;
}

/* Stack buttons vertically on small screens */
@media (max-width: 480px) {
  .form-container {
    max-width: 100%;
    margin: 10px 0;
  }
  .form-container button {
    display: block;
    width: 100%;
    margin: 8px 0 0 0;
  }
}

    </style>
</head>
<body>
    <div class="admin-wrapper">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="<?php echo htmlspecialchars($settings['logo_path'] ?? ''); ?>" alt="Logo" class="logo-img" />
                <span><?php echo htmlspecialchars($settings['business_name'] ?? ''); ?></span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
                    <p>Manage student enrollments, attendance, and notifications.</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username'] ?? ''); ?></span>
                </div>
            </header>

            <?php if ($statusMsg): ?>
                <div class="status-msg <?php echo strpos(strtolower($statusMsg), 'success') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($statusMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Notifications Section -->
            <section>
                <h2><i class="fas fa-bell"></i> Notifications & Reminders</h2>
                <?php if (empty($notifications)): ?>
                    <p>No new notifications.</p>
                <?php else: ?>
                    <table class="notifications-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Message</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notif): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($notif['type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($notif['recipient_email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($notif['message'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($notif['created_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- Enrollment Table -->
            <section>
                <h2><i class="fas fa-users"></i> Student Enrollments</h2>
                <table class="enrollment-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Lesson</th>
                            <th>Payment Status</th>
                            <th>Enrolled At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrollments)): ?>
                            <tr><td colspan="7" style="text-align:center;">No student enrollments available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td><?php echo $enrollment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['lesson_title']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['payment_status']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['enrolled_at']); ?></td>
                                    <td class="action-links">
                                        <!-- Edit Form Trigger -->
                                        <a href="#" onclick="document.getElementById('edit-form-<?php echo $enrollment['id']; ?>').style.display='block';return false;" title="Edit Enrollment"><i class="fas fa-edit"></i></a>
                                        <!-- Delete Form Trigger -->
                                        <a href="#" onclick="if(confirm('Are you sure you want to delete this enrollment?')) { document.getElementById('delete-form-<?php echo $enrollment['id']; ?>').submit(); } return false;" class="delete" title="Delete Enrollment"><i class="fas fa-trash-alt"></i></a>
                                        
                                        <!-- Hidden Edit Form -->
                                        <div id="edit-form-<?php echo $enrollment['id']; ?>" style="display:none; padding: 10px; background:#f9f9f9; border:1px solid #ccc; position:absolute; z-index:100; width: 350px;">
                                            <form method="POST" onsubmit="return confirm('Save changes to this enrollment?');">
                                                <input type="hidden" name="action" value="update_enrollment" />
                                                <input type="hidden" name="id" value="<?php echo $enrollment['id']; ?>" />
                                                <label>Full Name:</label>
                                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($enrollment['full_name']); ?>" required />
                                                <label>Email:</label>
                                                <input type="email" name="email" value="<?php echo htmlspecialchars($enrollment['email']); ?>" required />
                                                <label>Payment Status:</label>
                                                <select name="payment_status" required>
                                                    <option value="Paid" <?php if ($enrollment['payment_status'] === 'Paid') echo 'selected'; ?>>Paid</option>
                                                    <option value="Pending" <?php if ($enrollment['payment_status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                                    <option value="Failed" <?php if ($enrollment['payment_status'] === 'Failed') echo 'selected'; ?>>Failed</option>
                                                </select>
                                                <br/><br/>
                                                <button type="submit">Save</button>
                                                <button type="button" onclick="document.getElementById('edit-form-<?php echo $enrollment['id']; ?>').style.display='none';">Cancel</button>
                                            </form>
                                        </div>

                                        <!-- Hidden Delete Form -->
                                        <form id="delete-form-<?php echo $enrollment['id']; ?>" method="POST" style="display:none;">
                                            <input type="hidden" name="action" value="delete_enrollment" />
                                            <input type="hidden" name="id" value="<?php echo $enrollment['id']; ?>" />
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Attendance Section -->
            <section>
                <h2><i class="fas fa-calendar-check"></i> Attendance Records</h2>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Lesson</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceRecords)): ?>
                            <tr><td colspan="6" style="text-align:center;">No attendance records available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendanceRecords as $att): ?>
                                <tr>
                                    <td><?php echo $att['id']; ?></td>
                                    <td><?php echo htmlspecialchars($att['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($att['title']); ?></td>
                                    <td><?php echo htmlspecialchars($att['attendance_date']); ?></td>
                                    <td><?php echo htmlspecialchars($att['status']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="mark_attendance" />
                                            <input type="hidden" name="user_id" value="<?php echo $att['user_id']; ?>" />
                                            <input type="hidden" name="lesson_id" value="<?php echo $att['lesson_id']; ?>" />
                                            <select name="attendance_status" required>
                                                <option value="Present" <?php if ($att['status'] === 'Present') echo 'selected'; ?>>Present</option>
                                                <option value="Absent" <?php if ($att['status'] === 'Absent') echo 'selected'; ?>>Absent</option>
                                            </select>
                                            <button type="submit">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </div>
    </div>

    <script>
        // Simple script to close edit forms if user clicks outside
        document.addEventListener('click', function(event) {
            var editForms = document.querySelectorAll('[id^="edit-form-"]');
            editForms.forEach(function(form) {
                if (!form.contains(event.target) && !event.target.matches('.fas.fa-edit')) {
                    form.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
