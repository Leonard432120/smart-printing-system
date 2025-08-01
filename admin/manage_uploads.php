<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and includes
session_start();
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$success = $error = '';

// Function to send print notification email
function sendPrintNotification($conn, $transaction_id) {
    try {
        // Get transaction details
        $stmt = $conn->prepare("SELECT t.*, u.email, u.name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();

        if (!$transaction) return false;

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ict-01-25-22@unilia.ac.mw';
        $mail->Password = 'ifid zzgh jhik oync';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ict-01-25-22@unilia.ac.mw', 'Smart Printing System');
        $mail->addAddress($transaction['email'], $transaction['customer_name']);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your Document is Ready for Pickup';
        
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { color: #0a3d62; font-size: 24px; margin-bottom: 20px; }
                    .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                    .footer { margin-top: 20px; font-size: 14px; color: #777; }
                </style>
            </head>
            <body>
                <div class='header'>Your Document is Ready!</div>
                <p>Dear {$transaction['customer_name']},</p>
                
                <div class='details'>
                    <p><strong>Document Details:</strong></p>
                    <p>Reference: {$transaction['reference_code']}</p>
                    <p>Copies: {$transaction['copies']} ({$transaction['color_type']}, {$transaction['page_size']})</p>
                    <p>Amount Paid: MWK " . number_format($transaction['total_amount'], 2) . "</p>
                </div>
                
                <p>You can collect your printed documents at our office during working hours (8:00 AM - 5:00 PM).</p>
                
                <div class='footer'>
                    <p>Thank you for using our printing services!</p>
                    <p>Smart Printing System Team</p>
                </div>
            </body>
            </html>
        ";

        $mail->AltBody = "Dear {$transaction['customer_name']},\n\n" .
                        "Your document (Reference: {$transaction['reference_code']}) has been printed and is ready for pickup.\n\n" .
                        "Details:\n" .
                        "- Copies: {$transaction['copies']} ({$transaction['color_type']}, {$transaction['page_size']})\n" .
                        "- Amount Paid: MWK " . number_format($transaction['total_amount'], 2) . "\n\n" .
                        "You can collect during working hours (8:00 AM - 5:00 PM).\n\n" .
                        "Thank you,\nSmart Printing System Team";

        // Send email and log notification
        if ($mail->send()) {
            $logStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)");
            $type = 'Print Completion';
            $message = "Your document (Ref: {$transaction['reference_code']}) is ready for pickup";
            $logStmt->bind_param("iss", $transaction['user_id'], $type, $message);
            $logStmt->execute();
            
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $transaction_id = intval($_POST['transaction_id']);
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $transaction_id);
        
        if ($stmt->execute()) {
            // If marking as printed, send notification
            if ($new_status === 'Completed') {
                if (sendPrintNotification($conn, $transaction_id)) {
                    $success = "Status updated and customer notified successfully!";
                } else {
                    $success = "Status updated but failed to send notification email";
                }
            } else {
                $success = "Status updated successfully!";
            }
            
            // Log the action
            $logStmt = $conn->prepare("INSERT INTO audit_trails (user_id, action, description) VALUES (?, ?, ?)");
            $user_id = $_SESSION['users']['id'];
            $action = 'Update Print Status';
            $description = "Changed status of transaction #$transaction_id to $new_status";
            $logStmt->bind_param("iss", $user_id, $action, $description);
            $logStmt->execute();
            
        } else {
            $error = "Failed to update status: " . $conn->error;
        }
    }
}

// Fetch only paid transactions with optional status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT t.*, u.name as user_name, u.email as user_email, p.status as payment_status 
          FROM transactions t
          JOIN users u ON t.user_id = u.id
          JOIN payments p ON p.transaction_id = t.id
          WHERE p.status = 'success'"; // Only show paid transactions
          
if (!empty($status_filter)) {
    $query .= " AND t.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$query .= " ORDER BY t.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printing Requests | Admin Panel</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0a3d62;
            --primary-light: #1e5d8a;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        .dashboard-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .filter-card {
            background: white;
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .requests-table th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .requests-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-uploaded { background-color: rgba(52, 152, 219, 0.1); color: #3498db; }
        .status-processing { background-color: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .status-completed { background-color: rgba(39, 174, 96, 0.1); color: var(--success); }
        .status-rejected { background-color: rgba(231, 76, 60, 0.1); color: var(--danger); }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-print { background-color: var(--success); color: white; }
        .btn-print:hover { background-color: #219653; }
        
        .btn-reject { background-color: var(--danger); color: white; }
        .btn-reject:hover { background-color: #c0392b; }
        
        .notification-status {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        .notification-success { color: var(--success); }
        .notification-failed { color: var(--danger); }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #777;
        }
        
        .btn-view {
            color: #0a3d62;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: rgba(10, 61, 98, 0.1);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 2px 0;
        }
        
        .btn-view:hover {
            background-color: rgba(10, 61, 98, 0.2);
            text-decoration: none;
        }
        
        .text-danger {
            color: #e74c3c;
        }
        
        .file-info {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-top: 3px;
            word-break: break-all;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .d-block {
            display: block;
        }
        
        .file-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="logo-img">
            <span><?= htmlspecialchars($settings['business_name']) ?></span>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_resources.php"><i class="fas fa-tachometer"></i> Manage Resources</a>
        <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
        <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
        <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
        <a href="manage_uploads.php" class="active"><i class="fas fa-print"></i> Printing</a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="logout.php" style="color: red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-print"></i> Printing Requests</h1>
                <p>Manage all document printing requests from users</p>
            </div>
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['users']['username']) ?></span>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="filter-card">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Uploaded" <?= $status_filter === 'Uploaded' ? 'selected' : '' ?>>Uploaded</option>
                            <option value="Processing" <?= $status_filter === 'Processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Rejected" <?= $status_filter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="table-responsive">
                <?php if ($result->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-print fa-3x"></i>
                        <h3>No Paid Printing Requests Found</h3>
                        <p>There are currently no paid documents awaiting processing.</p>
                        <?php if (!empty($status_filter)): ?>
                            <a href="?status=" class="action-btn btn-print" style="margin-top: 15px;">
                                <i class="fas fa-undo"></i> Clear Filter
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Reference</th>
                                <th>Document</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['customer_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($row['customer_contact']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['reference_code']) ?></td>
                                    <td>
                                        <?php
                                        // Define the correct absolute server path
                                        $baseDir = 'C:/wamp64/www/smart-printing-system/uploads/notes/';
                                        
                                        // Get just the filename from the stored path
                                        $filename = basename($row['file_name']);
                                        
                                        // Build full server path
                                        $fullPath = $baseDir . $filename;
                                        
                                        // Web-accessible path (same as stored in database)
                                        $webPath = $row['file_name'];
                                        
                                        if (file_exists($fullPath)): ?>
                                            <div class="file-actions">
                                           
                                                <a href="download.php?id=<?= $row['id'] ?>" 
                                                   class="btn-view"
                                                   title="Download with original filename">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <small class="file-info"><?= htmlspecialchars($row['original_filename'] ?? $filename) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> File Missing
                                            </span>
                                            <small class="d-block text-muted">Please check server path</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['copies'] ?> copies<br>
                                        <?= htmlspecialchars($row['color_type']) ?>, <?= htmlspecialchars($row['page_size']) ?>
                                    </td>
                                    <td>MWK <?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php if ($row['status'] !== 'Completed' && $row['status'] !== 'Rejected'): ?>
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="transaction_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="new_status" value="Completed">
                                                <button type="submit" class="action-btn btn-print" 
                                                        onclick="return confirm('Mark this document as printed?')">
                                                    <i class="fas fa-check"></i> Print
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="transaction_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" class="action-btn btn-reject" 
                                                        onclick="return confirm('Reject this printing request?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Processed</span>
                                            <?php if ($row['status'] === 'Completed'): ?>
                                                <div class="notification-status notification-success">
                                                    <i class="fas fa-check-circle"></i> Notified
                                                </div>
                                                
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Confirm before rejecting
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter when selection changes
    document.getElementById('status').addEventListener('change', function() {
        if (this.value) this.form.submit();
    });
    
    // Add confirmation for all form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>