<?php
// Module 1: Session and Dependencies
session_start();
include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Module 2: Load Settings
$settings = [
    'business_name' => 'Mungu Ni Dawa',
    'logo_path' => '/smart-printing-system/assets/images/MND.jpeg'
];
$settingsResult = $conn->query("SELECT business_name, logo_path FROM settings WHERE id = 1");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $settings = $settingsResult->fetch_assoc();
}

// Module 3: PayChangu API Verification Function
function verifyPaymentStatus($transaction_id) {
    include_once __DIR__ . '/../config/config.php';
    $secret = defined('PAYCHANGU_API_KEY') ? PAYCHANGU_API_KEY : '';
    $url = "https://api.paychangu.com/verify-payment/$transaction_id";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer $secret"
        ]
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    file_put_contents(__DIR__ . "/paychangu_debug.txt", "TX_REF: $transaction_id\nRESPONSE:\n$response\nERROR:\n$err\n", FILE_APPEND);

    if ($err) {
        return ['status' => 'error', 'message' => "cURL Error: $err"];
    }

    $data = json_decode($response, true);
    if (isset($data['data']['status'])) {
        return ['status' => $data['data']['status'], 'message' => $data['message'] ?? ''];
    }
    return ['status' => 'unknown', 'message' => 'No status returned'];
}

// Module 4: Form Processing
$statusMsg = '';
$perPage = 10;
$page = isset($_GET['page']) ? max(1, filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT)) : 1;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : '';
$startDate = isset($_GET['start_date']) ? filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) : '';
$endDate = isset($_GET['end_date']) ? filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Update Transaction Status
        if ($_POST['action'] === 'update_transaction') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $validStatuses = ['pending', 'completed', 'failed'];
            if (in_array($status, $validStatuses)) {
                $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $id);
                $statusMsg = $stmt->execute() ? "Transaction status updated successfully!" : "Error updating transaction: " . $conn->error;
            } else {
                $statusMsg = "Invalid status selected.";
            }
        }
        // Delete Transaction
        elseif ($_POST['action'] === 'delete_transaction') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $statusMsg = $stmt->execute() ? "Transaction deleted successfully!" : "Error deleting transaction: " . $conn->error;
        }
        // Bulk Actions
        elseif ($_POST['action'] === 'bulk_action' && isset($_POST['bulk_action_type']) && isset($_POST['transaction_ids'])) {
            $bulkAction = filter_input(INPUT_POST, 'bulk_action_type', FILTER_SANITIZE_STRING);
            $transactionIds = array_map('intval', $_POST['transaction_ids']);
            if (empty($transactionIds)) {
                $statusMsg = "No transactions selected.";
            } else {
                if ($bulkAction === 'update_status' && isset($_POST['bulk_status'])) {
                    $status = filter_input(INPUT_POST, 'bulk_status', FILTER_SANITIZE_STRING);
                    $validStatuses = ['pending', 'completed', 'failed'];
                    if (in_array($status, $validStatuses)) {
                        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                        $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id IN ($placeholders)");
                        $stmt->bind_param("s" . str_repeat('i', count($transactionIds)), $status, ...$transactionIds);
                        $statusMsg = $stmt->execute() ? "Bulk status update successful!" : "Error updating transactions: " . $conn->error;
                    } else {
                        $statusMsg = "Invalid bulk status selected.";
                    }
                } elseif ($bulkAction === 'delete') {
                    $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM payments WHERE id IN ($placeholders)");
                    $stmt->bind_param(str_repeat('i', count($transactionIds)), ...$transactionIds);
                    $statusMsg = $stmt->execute() ? "Bulk deletion successful!" : "Error deleting transactions: " . $conn->error;
                }
            }
        }
    }
}

// Module 5: Data Fetching
$whereClauses = [];
$params = [];
$paramTypes = '';

if ($search) {
    $whereClauses[] = "(p.email LIKE ? OR p.transaction_id LIKE ? OR p.status LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $paramTypes = 'sss';
}
if ($startDate && $endDate) {
    $whereClauses[] = "p.payment_date BETWEEN ? AND ?";
    $params[] = $startDate . ' 00:00:00';
    $params[] = $endDate . ' 23:59:59';
    $paramTypes .= 'ss';
}
$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Fetch paginated transactions
$query = "
    SELECT p.id, p.transaction_id, p.email, p.order_id, p.lesson_id, p.amount, p.status, p.payment_date, p.confirmed,
           o.full_name, o.phone, o.notes, o.created_at AS order_created_at, 
           s.name AS service_name, l.title AS lesson_title
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN lessons l ON p.lesson_id = l.id
    $whereSql
    ORDER BY p.payment_date DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$paramTypes = $paramTypes ? $paramTypes . 'ii' : 'ii';
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    if ($row['confirmed'] == 0) {
        $apiStatus = verifyPaymentStatus($row['transaction_id'], __DIR__ . '/../config.php');
        $row['api_status'] = $apiStatus['status'];
        $row['api_message'] = $apiStatus['message'];
        if ($apiStatus['status'] === 'success') {
            $stmtUpdate = $conn->prepare("UPDATE payments SET status = ?, confirmed = 1 WHERE id = ?");
            $apiStatusValue = 'success';
            $stmtUpdate->bind_param("si", $apiStatusValue, $row['id']);
            $stmtUpdate->execute();
        }
    } else {
        $row['api_status'] = $row['status'];
        $row['api_message'] = 'Already confirmed';
    }
    $transactions[] = $row;
}

// Total records for pagination
$totalQuery = "SELECT COUNT(*) as total FROM payments p $whereSql";
$stmt = $conn->prepare($totalQuery);

if ($whereSql && $params) {
    // Exclude last two params (LIMIT and OFFSET) for count query
    $bindTypes = substr($paramTypes, 0, -2);
    $bindValues = array_slice($params, 0, -2);
    $stmt->bind_param($bindTypes, ...$bindValues);
}

$stmt->execute();
$result = $stmt->get_result();
$totalRow = $result->fetch_assoc();
$totalRecords = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = ceil($totalRecords / $perPage);

// Module 6: CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'Customer Email', 'Order/Lesson', 'Customer Name', 'Phone', 'Amount', 'Status', 'API Status', 'Payment Date']);
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
            $t['api_status'],
            date('Y-m-d H:i', strtotime($t['payment_date']))
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
    <title>Manage Transactions | <?php echo htmlspecialchars($settings['business_name']); ?></title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .transaction-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .transaction-table th, .transaction-table td {
            padding: 16px;
        }
        .status-msg {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen admin-wrapper">
        <!-- Module 7: Sidebar -->
        <div class="sidebar w-64 bg-gray-800 text-white p-6">
            <div class="flex items-center mb-8 logo">
            <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
            <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
      </div>
            <a href="dashboard.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-tachometer-alt mr-3"></i> Dashboard</a>
            <a href="manage_lessons.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-book mr-3"></i> Lessons</a>
            <a href="manage_prices.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-tag mr-3"></i> Prices</a>
            <a href="manage_services.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-cogs mr-3"></i> Services</a>
            <a href="manage_students.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-user-graduate mr-3"></i> Students</a>
            <a href="manage_transactions.php" class="flex items-center py-2 px-4 bg-gray-700 rounded active"><i class="fas fa-credit-card mr-3"></i> Transactions</a>
            <a href="manage_users.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-users mr-3"></i> Users</a>
            <a href="settings.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded"><i class="fas fa-cog mr-3"></i> Settings</a>
            <a href="../logout.php" class="flex items-center py-2 px-4 hover:bg-gray-700 rounded text-red-400" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
        </div>

        <!-- Module 8: Main Content -->
        <div class="flex-1 p-6 main-content">
            <header class="dashboard-header flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-credit-card mr-2"></i> Manage Transactions</h1>
                    <p class="text-gray-600">Manage payment transactions for <?php echo htmlspecialchars($settings['business_name']); ?> services and lessons.</p>
                </div>
                <div class="user-profile flex items-center">
                    <i class="fas fa-user-circle text-2xl text-gray-600 mr-2"></i>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <?php if (isset($statusMsg)): ?>
                <div class="status-msg <?php echo strpos($statusMsg, 'success') !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> p-4 rounded-lg mb-6">
                    <?php echo htmlspecialchars($statusMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Module 9: Search and Filter -->
            <div class="mb-6 bg-white p-4 rounded-lg shadow">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email, transaction ID, or status" class="flex-1 p-2 border rounded-lg">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="p-2 border rounded-lg">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="p-2 border rounded-lg">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filter</button>
                    <a href="?export=csv" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Export to CSV</a>
                </form>
            </div>

            <!-- Module 10: Bulk Actions -->
            <form method="POST" id="bulkActionForm" class="mb-4">
                <input type="hidden" name="action" value="bulk_action">
                <div class="flex gap-4">
                    <select name="bulk_action_type" class="p-2 border rounded-lg">
                        <option value="">Select Bulk Action</option>
                        <option value="update_status">Update Status</option>
                        <option value="delete">Delete</option>
                    </select>
                    <select name="bulk_status" class="p-2 border rounded-lg hidden" id="bulkStatusSelect">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Apply</button>
                </div>
            </form>

            <!-- Module 11: Transactions Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="transaction-table w-full">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Transaction ID</th>
                            <th>Customer Email</th>
                            <th>Order/Lesson</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>API Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-gray-600">No transactions available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="hover:bg-gray-50">
                                    <td><input type="checkbox" name="transaction_ids[]" form="bulkActionForm" value="<?php echo $transaction['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['email']); ?></td>
                                    <td>
                                        <?php
                                        if ($transaction['order_id']) {
                                            echo 'Order #' . $transaction['order_id'] . ' (' . htmlspecialchars($transaction['service_name']) . ')';
                                        } elseif ($transaction['lesson_id']) {
                                            echo 'Lesson: ' . htmlspecialchars($transaction['lesson_title']);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['full_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['phone'] ?: 'N/A'); ?></td>
                                    <td><?php echo formatCurrency($transaction['amount']); ?></td>
                                    <td>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="update_transaction">
                                            <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                                            <select name="status" class="p-2 border rounded-lg" required>
                                                <option value="pending" <?php echo $transaction['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?php echo $transaction['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="failed" <?php echo $transaction['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 ml-2">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'success' => 'text-green-600 font-bold',
                                            'pending' => 'text-yellow-600 font-bold',
                                            'failed' => 'text-red-600 font-bold',
                                            'error' => 'text-red-600'
                                        ];
                                        $statusClass = $statusClasses[$transaction['api_status']] ?? 'text-gray-600';
                                        if ($transaction['api_status'] === 'success' && $transaction['status'] !== 'completed' && $transaction['status'] !== 'failed') {
                                            echo '<span class="' . $statusClass . '">Success (Action Required)</span>';
                                        } else {
                                            echo '<span class="' . $statusClass . '">' . ucfirst($transaction['api_status']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['payment_date'])); ?></td>
                                    <td class="flex gap-2">
                                        <a href="#" class="text-blue-600 hover:underline" onclick="showDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">View</a>
                                        <a href="#" class="text-red-600 hover:underline" onclick="if(confirm('Are you sure?')){deleteTransaction(<?php echo $transaction['id']; ?>)}">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Module 12: Pagination -->
            <div class="flex justify-between items-center mt-4">
                <p class="text-gray-600">Showing <?php echo count($transactions); ?> of <?php echo $totalRecords; ?> transactions</p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="px-3 py-2 rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Module 13: Modal for Transaction Details -->
            <div id="detailsModal" class="modal">
                <div class="modal-content">
                    <h2 class="text-xl font-bold mb-4">Transaction Details</h2>
                    <div id="modalBody"></div>
                    <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Module 14: JavaScript -->
    <script>
        function deleteTransaction(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete_transaction"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function showDetails(transaction) {
            const modal = document.getElementById('detailsModal');
            const modalBody = document.getElementById('modalBody');
            const orderLesson = transaction.order_id ? `Order #${transaction.order_id} (${transaction.service_name})` : (transaction.lesson_id ? `Lesson: ${transaction.lesson_title}` : 'N/A');
            modalBody.innerHTML = `
                <p><strong>Transaction ID:</strong> ${transaction.transaction_id}</p>
                <p><strong>Customer Email:</strong> ${transaction.email}</p>
                <p><strong>Order/Lesson:</strong> ${orderLesson}</p>
                <p><strong>Customer Name:</strong> ${transaction.full_name || 'N/A'}</p>
                <p><strong>Phone:</strong> ${transaction.phone || 'N/A'}</p>
                <p><strong>Amount:</strong> ${transaction.amount.toLocaleString('en-MW', { style: 'currency', currency: 'MWK' })}</p>
                <p><strong>Status:</strong> ${transaction.status}</p>
                <p><strong>API Status:</strong> ${transaction.api_status}</p>
                <p><strong>API Message:</strong> ${transaction.api_message}</p>
                <p><strong>Payment Date:</strong> ${new Date(transaction.payment_date).toLocaleString()}</p>
                <p><strong>Notes:</strong> ${transaction.notes || 'N/A'}</p>
            `;
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('input[name="transaction_ids[]"]').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        document.querySelector('select[name="bulk_action_type"]').addEventListener('change', function() {
            const bulkStatusSelect = document.getElementById('bulkStatusSelect');
            bulkStatusSelect.classList.toggle('hidden', this.value !== 'update_status');
        });

        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
