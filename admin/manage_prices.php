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

// Module 2: Form Processing
$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_price') {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if ($price >= 0) {
        $stmt = $conn->prepare("UPDATE services SET price = ? WHERE id = ?");
        $stmt->bind_param("di", $price, $id);
        if ($stmt->execute()) {
            $statusMsg = "Price updated successfully!";
        } else {
            $statusMsg = "Error updating price: " . $conn->error;
        }
    } else {
        $statusMsg = "Price must be a positive number.";
    }
}

// Module 3: Data Fetching
// Fetch all services
$services = [];
$result = $conn->query("SELECT id, name, price FROM services ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch orders for each service
$orders = [];
foreach ($services as $service) {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, notes, status, created_at FROM orders WHERE service_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $service['id']);
    $stmt->execute();
    $orders[$service['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prices | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    body {
        background: #f4f6f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .service-table, .order-table {
        width: 100%;
        border-collapse: collapse;
        margin: 30px 0;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }

    .service-table th, .order-table th {
        background: #0a3d62;
        color: #fff;
        font-weight: 600;
        padding: 14px 18px;
        text-align: left;
    }

    .service-table td, .order-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #eee;
        font-size: 0.95rem;
        color: #333;
    }

    .service-table tr:last-child td,
    .order-table tr:last-child td {
        border-bottom: none;
    }

    .form-container {
        background: #ffffff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        margin: 10px 0;
    }

    .form-container input[type="number"] {
        width: 100%;
        padding: 10px 14px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: border-color 0.3s;
    }

    .form-container input[type="number"]:focus {
        border-color: #0a3d62;
        outline: none;
        box-shadow: 0 0 0 2px rgba(10, 61, 98, 0.15);
    }

    .form-container button {
        width: 100%;
        padding: 10px;
        background-color: #0a3d62;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .form-container button:hover {
        background-color: #064173;
    }

    .status-msg {
        padding: 12px;
        margin: 20px 0;
        border-radius: 8px;
        font-weight: 500;
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

    h2 {
        margin-top: 30px;
        font-size: 1.5rem;
        color: #0a3d62;
        border-bottom: 2px solid #ddd;
        padding-bottom: 8px;
    }

    .action-links a {
        margin-right: 10px;
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
    }

    .action-links a:hover {
        text-decoration: underline;
    }
</style>

</head>
<body>
    <div class="admin-wrapper">
        <!-- Module 4: Sidebar -->
        <div class="sidebar">
            <div class="logo">
            <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
            <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php" class="active"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Module 5: Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-tag"></i> Manage Prices</h1>
                    <p>Manage service prices for Mungu Ni Dawa.</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <?php if (isset($statusMsg)): ?>
                <div class="status-msg <?php echo strpos($statusMsg, 'success') !== false ? 'success' : 'error'; ?>">
                    <?php echo $statusMsg; ?>
                </div>
            <?php endif; ?>

            <!-- Module 6: Services Table with Price Update -->
            <table class="service-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Current Price</th>
                        <th>Update Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No services available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo formatCurrency($service['price']); ?></td>
                                <td>
                                    <form method="POST" class="form-container">
                                        <input type="hidden" name="action" value="update_price">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <input type="number" name="price" value="<?php echo $service['price']; ?>" step="0.01" min="0" required>
                                        <button type="submit">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Module 7: Orders Table -->
            <?php foreach ($services as $service): ?>
                <h2>Orders for <?php echo htmlspecialchars($service['name']); ?></h2>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders[$service['id']])): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No orders for this service.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders[$service['id']] as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($order['notes'] ?: 'N/A'); ?></td>
                                    <td><?php echo ucfirst($order['status']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>





