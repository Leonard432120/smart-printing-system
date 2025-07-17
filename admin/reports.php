<?php
// Module 1: Session and Dependencies
session_start();
include '../user/includes/db_connect.php';
include 'includes/functions.php';

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Module 2: Data Fetching
// Fetch Users
$users = [];
$userResult = $conn->query("
    SELECT u.id, u.username, u.email, u.role, u.created_at,
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
           (SELECT COUNT(*) FROM enrollments e WHERE e.email = u.email) AS enrollment_count
    FROM users u
    ORDER BY u.created_at DESC
");
while ($row = $userResult->fetch_assoc()) {
    $users[] = $row;
}
$totalUsers = count($users);

// Fetch Transactions
$transactions = [];
$transactionResult = $conn->query("
    SELECT p.id, p.transaction_id, p.email, p.order_id, p.lesson_id, p.amount, p.status, p.payment_date, p.confirmed,
           o.full_name, o.phone, o.notes, o.created_at AS order_created_at, 
           s.name AS service_name, l.title AS lesson_title
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN lessons l ON p.lesson_id = l.id
    ORDER BY p.payment_date DESC
");
while ($row = $transactionResult->fetch_assoc()) {
    $transactions[] = $row;
}
$totalTransactions = count($transactions);
$statusCounts = ['completed' => 0, 'pending' => 0, 'failed' => 0, 'success' => 0];
$totalRevenue = 0;
foreach ($transactions as $t) {
    $statusCounts[$t['status']] = ($statusCounts[$t['status']] ?? 0) + 1;
    if ($t['status'] === 'completed') {
        $totalRevenue += $t['amount'];
    }
}

// Fetch Lessons
$lessons = [];
$lessonResult = $conn->query("
    SELECT l.id, l.title, l.fee, l.duration_weeks, l.created_at,
           (SELECT COUNT(*) FROM enrollments e WHERE e.lesson_id = l.id) AS enrollment_count
    FROM lessons l
    ORDER BY l.created_at DESC
");
while ($row = $lessonResult->fetch_assoc()) {
    $lessons[] = $row;
}
$totalLessons = count($lessons);

// Fetch Orders
$orders = [];
$orderResult = $conn->query("
    SELECT o.id, o.user_id, o.service_id, o.full_name, o.email, o.phone, o.status, o.created_at,
           s.name AS service_name
    FROM orders o
    JOIN services s ON o.service_id = s.id
    ORDER BY o.created_at DESC
");
while ($row = $orderResult->fetch_assoc()) {
    $orders[] = $row;
}
$totalOrders = count($orders);

// Fetch Enrollments
$enrollments = [];
$enrollmentResult = $conn->query("
    SELECT e.id, e.full_name, e.email, e.lesson_id, e.payment_status, e.enrolled_at,
           l.title AS lesson_title
    FROM enrollments e
    JOIN lessons l ON e.lesson_id = l.id
    ORDER BY e.enrolled_at DESC
");
while ($row = $enrollmentResult->fetch_assoc()) {
    $enrollments[] = $row;
}
$totalEnrollments = count($enrollments);

// Module 3: CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mungu_ni_dawa_report_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Summary
    fputcsv($output, ['Mungu Ni Dawa System Report', 'Generated on ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Total Users', $totalUsers]);
    fputcsv($output, ['Total Transactions', $totalTransactions]);
    fputcsv($output, ['Total Revenue (MWK)', number_format($totalRevenue, 2)]);
    fputcsv($output, ['Completed Transactions', $statusCounts['completed']]);
    fputcsv($output, ['Pending Transactions', $statusCounts['pending']]);
    fputcsv($output, ['Failed Transactions', $statusCounts['failed']]);
    fputcsv($output, ['Success Transactions', $statusCounts['success']]);
    fputcsv($output, ['Total Lessons', $totalLessons]);
    fputcsv($output, ['Total Orders', $totalOrders]);
    fputcsv($output, ['Total Enrollments', $totalEnrollments]);
    fputcsv($output, []);
    
    // Users
    fputcsv($output, ['Users']);
    fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Orders', 'Enrollments', 'Created At']);
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['email'],
            ucfirst($user['role']),
            $user['order_count'],
            $user['enrollment_count'],
            date('Y-m-d H:i', strtotime($user['created_at']))
        ]);
    }
    
    // Transactions
    fputcsv($output, []);
    fputcsv($output, ['Transactions']);
    fputcsv($output, ['Transaction ID', 'Email', 'Order/Lesson', 'Customer Name', 'Phone', 'Amount', 'Status', 'Payment Date', 'Confirmed']);
    foreach ($transactions as $t) {
        $orderLesson = $t['order_id'] ? "Order #{$t['order_id']} ({$t['service_name']})" : ($t['lesson_id'] ? "Lesson: {$t['lesson_title']}" : 'N/A');
        fputcsv($output, [
            $t['transaction_id'],
            $t['email'],
            $orderLesson,
            $t['full_name'] ?: 'N/A',
            $t['phone'] ?: 'N/A',
            formatCurrency($t['amount']),
            $t['status'],
            date('Y-m-d H:i', strtotime($t['payment_date'])),
            $t['confirmed'] ? 'Yes' : 'No'
        ]);
    }
    
    // Lessons
    fputcsv($output, []);
    fputcsv($output, ['Lessons']);
    fputcsv($output, ['ID', 'Title', 'Fee (MWK)', 'Duration (Weeks)', 'Enrollments', 'Created At']);
    foreach ($lessons as $l) {
        fputcsv($output, [
            $l['id'],
            $l['title'],
            formatCurrency($l['fee']),
            $l['duration_weeks'],
            $l['enrollment_count'],
            date('Y-m-d H:i', strtotime($l['created_at']))
        ]);
    }
    
    // Orders
    fputcsv($output, []);
    fputcsv($output, ['Orders']);
    fputcsv($output, ['ID', 'User ID', 'Service', 'Customer Name', 'Email', 'Phone', 'Status', 'Created At']);
    foreach ($orders as $o) {
        fputcsv($output, [
            $o['id'],
            $o['user_id'],
            $o['service_name'],
            $o['full_name'],
            $o['email'],
            $o['phone'],
            $o['status'],
            date('Y-m-d H:i', strtotime($o['created_at']))
        ]);
    }
    
    // Enrollments
    fputcsv($output, []);
    fputcsv($output, ['Enrollments']);
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Lesson', 'Payment Status', 'Enrolled At']);
    foreach ($enrollments as $e) {
        fputcsv($output, [
            $e['id'],
            $e['full_name'],
            $e['email'],
            $e['lesson_title'],
            $e['payment_status'],
            date('Y-m-d H:i', strtotime($e['enrolled_at']))
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Report | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .report-table th, .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .report-table th {
            background: #3498db;
            color: #fff;
        }
        .summary-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .summary-container h3 {
            margin-top: 0;
        }
        .export-button {
            padding: 10px 20px;
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .export-button:hover {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Module 4: Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="/smart-printing-system/assets/images/MND.jpeg" alt="Logo" class="logo-img">
                <span>Mungu Ni Dawa</span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="report.php" class="active"><i class="fas fa-file-alt"></i> Report</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Module 5: Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-file-alt"></i> System Report</h1>
                    <p>Comprehensive report of all activities in the Mungu Ni Dawa system.</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <!-- Module 6: Summary -->
            <div class="summary-container">
                <h3>Summary</h3>
                <p>Total Users: <?php echo $totalUsers; ?></p>
                <p>Total Transactions: <?php echo $totalTransactions; ?></p>
                <p>Total Revenue (MWK): <?php echo formatCurrency($totalRevenue); ?></p>
                <p>Completed Transactions: <?php echo $statusCounts['completed']; ?></p>
                <p>Pending Transactions: <?php echo $statusCounts['pending']; ?></p>
                <p>Failed Transactions: <?php echo $statusCounts['failed']; ?></p>
                <p>Success Transactions: <?php echo $statusCounts['success']; ?></p>
                <p>Total Lessons: <?php echo $totalLessons; ?></p>
                <p>Total Orders: <?php echo $totalOrders; ?></p>
                <p>Total Enrollments: <?php echo $totalEnrollments; ?></p>
                <a href="?export=csv" class="export-button">Export to CSV</a>
            </div>

            <!-- Module 7: Users Table -->
            <h2>Users</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Enrollments</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" style="text-align: center;">No users available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo $user['order_count']; ?></td>
                                <td><?php echo $user['enrollment_count']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Module 8: Transactions Table -->
            <h2>Transactions</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Email</th>
                        <th>Order/Lesson</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="9" style="text-align: center;">No transactions available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['transaction_id']); ?></td>
                                <td><?php echo htmlspecialchars($t['email']); ?></td>
                                <td><?php echo $t['order_id'] ? "Order #{$t['order_id']} (" . htmlspecialchars($t['service_name']) . ")" : ($t['lesson_id'] ? "Lesson: " . htmlspecialchars($t['lesson_title']) : 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($t['full_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($t['phone'] ?: 'N/A'); ?></td>
                                <td><?php echo formatCurrency($t['amount']); ?></td>
                                <td><?php echo htmlspecialchars($t['status']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($t['payment_date'])); ?></td>
                                <td><?php echo $t['confirmed'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Module 9: Lessons Table -->
            <h2>Lessons</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Fee (MWK)</th>
                        <th>Duration (Weeks)</th>
                        <th>Enrollments</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lessons)): ?>
                        <tr><td colspan="6" style="text-align: center;">No lessons available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lessons as $l): ?>
                            <tr>
                                <td><?php echo $l['id']; ?></td>
                                <td><?php echo htmlspecialchars($l['title']); ?></td>
                                <td><?php echo formatCurrency($l['fee']); ?></td>
                                <td><?php echo $l['duration_weeks']; ?></td>
                                <td><?php echo $l['enrollment_count']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($l['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Module 10: Orders Table -->
            <h2>Orders</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Service</th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" style="text-align: center;">No orders available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><?php echo $o['id']; ?></td>
                                <td><?php echo $o['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($o['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['email']); ?></td>
                                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                                <td><?php echo htmlspecialchars($o['status']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($o['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Module 11: Enrollments Table -->
            <h2>Enrollments</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Lesson</th>
                        <th>Payment Status</th>
                        <th>Enrolled At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr><td colspan="6" style="text-align: center;">No enrollments available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td><?php echo $e['id']; ?></td>
                                <td><?php echo htmlspecialchars($e['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($e['email']); ?></td>
                                <td><?php echo htmlspecialchars($e['lesson_title']); ?></td>
                                <td><?php echo htmlspecialchars($e['payment_status']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($e['enrolled_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>