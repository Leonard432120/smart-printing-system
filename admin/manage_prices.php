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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_service_price') {
        $id = intval($_POST['id']);
        $price = floatval($_POST['price']);

        if ($price >= 0) {
            $stmt = $conn->prepare("UPDATE services SET price = ? WHERE id = ?");
            $stmt->bind_param("di", $price, $id);
            if ($stmt->execute()) {
                $statusMsg = "✅ Service price updated.";
            } else {
                $statusMsg = "❌ Error: " . $conn->error;
            }
        } else {
            $statusMsg = "❌ Invalid service price.";
        }
    }

    if ($action === 'update_print_price') {
        $id = intval($_POST['id']);
        $price = floatval($_POST['price']);

        if ($price >= 0) {
            $stmt = $conn->prepare("UPDATE prices SET price = ? WHERE id = ?");
            $stmt->bind_param("di", $price, $id);
            if ($stmt->execute()) {
                $statusMsg = "✅ Printing price updated.";
            } else {
                $statusMsg = "❌ Error: " . $conn->error;
            }
        } else {
            $statusMsg = "❌ Invalid printing price.";
        }
    }

    if ($action === 'add_service_price') {
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        if ($name && $price >= 0) {
            $stmt = $conn->prepare("INSERT INTO services (name, price) VALUES (?, ?)");
            $stmt->bind_param("sd", $name, $price);
            $stmt->execute();
            $statusMsg = "✅ Service added successfully.";
        } else {
            $statusMsg = "❌ Invalid service input.";
        }
    }

    if ($action === 'add_print_price') {
        $size = $_POST['page_size'];
        $color = $_POST['color_type'];
        $price = floatval($_POST['price']);
        if ($size && $color && $price >= 0) {
            $stmt = $conn->prepare("INSERT INTO prices (page_size, color_type, price) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $size, $color, $price);
            $stmt->execute();
            $statusMsg = "✅ Print price added successfully.";
        } else {
            $statusMsg = "❌ Invalid print price input.";
        }
    }
}

// Module 3: Data Fetching
$services = $conn->query("SELECT id, name, price FROM services ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$printPrices = $conn->query("SELECT id, page_size, color_type, price FROM prices ORDER BY page_size")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Prices | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #f5f6fa; font-family: 'Segoe UI', sans-serif; }
    .admin-wrapper { display: flex; }

    .main-content { padding: 20px; flex-grow: 1; }
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

    h2 {
        color: #0a3d62;
        font-size: 1.5rem;
        margin-top: 30px;
        border-bottom: 2px solid #ccc;
        padding-bottom: 8px;
    }

    .status-msg {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 6px;
        text-align: center;
        font-weight: bold;
    }
    .status-msg.success { background-color: #2ecc71; color: #fff; }
    .status-msg.error { background-color: #e74c3c; color: #fff; }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        padding: 14px 18px;
        border-bottom: 1px solid #f0f0f0;
    }

    th {
        background-color: #0a3d62;
        color: white;
        text-align: left;
    }

    form input[type="number"],
    form input[type="text"],
    form select {
        padding: 8px;
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-top: 6px;
    }

    form button {
        padding: 8px 14px;
        background: #0a3d62;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 8px;
    }

    form button:hover {
        background: #064173;
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
        <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
        <a href="manage_prices.php" class="active"><i class="fas fa-tag"></i> Prices</a>
        <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="../logout.php" style="color: red;" onclick="return confirm('Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="dashboard-header">
            <div>
                <h1><i class="fas fa-tag"></i> Manage Prices</h1>
                <p>Update prices for services and document printing.</p>
            </div>
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['users']['username']) ?></span>
            </div>
        </header>

        <?php if ($statusMsg): ?>
            <div class="status-msg <?= str_contains($statusMsg, '✅') ? 'success' : 'error' ?>">
                <?= $statusMsg ?>
            </div>
        <?php endif; ?>

        <!-- Services Table -->
        <h2>📋 Service Prices</h2>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Current Price</th>
                    <th>Update Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['name']) ?></td>
                        <td><?= formatCurrency($service['price']) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="update_service_price">
                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                <input type="number" step="0.01" name="price" value="<?= $service['price'] ?>" required>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Add New Service -->
        <h2>➕ Add New Service</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_service_price">
            <input type="text" name="name" placeholder="Service Name" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <button type="submit">Add Service</button>
        </form>

        <!-- Print Prices Table -->
        <h2>🖨️ Printing Prices</h2>
        <table>
            <thead>
                <tr>
                    <th>Page Size</th>
                    <th>Color Type</th>
                    <th>Current Price</th>
                    <th>Update Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($printPrices as $price): ?>
                    <tr>
                        <td><?= $price['page_size'] ?></td>
                        <td><?= $price['color_type'] ?></td>
                        <td><?= formatCurrency($price['price']) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="update_print_price">
                                <input type="hidden" name="id" value="<?= $price['id'] ?>">
                                <input type="number" step="0.01" name="price" value="<?= $price['price'] ?>" required>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Add New Print Price -->
        <h2>➕ Add New Print Price</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_print_price">
            <select name="page_size" required>
                <option value="">Select Page Size</option>
                <option value="A4">A4</option>
                <option value="A5">A5</option>
                <option value="A3">A3</option>
            </select>
            <select name="color_type" required>
                <option value="">Select Color Type</option>
                <option value="Color">Color</option>
                <option value="Black & White">Black & White</option>
            </select>
            <input type="number" step="0.01" name="price" placeholder="Price (MWK)" required>
            <button type="submit">Add Print Price</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
