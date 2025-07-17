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
    if (isset($_POST['action'])) {
        // Add Service
        if ($_POST['action'] === 'add_service') {
         $name = htmlspecialchars(trim($_POST['name']));
          $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/';
                $imageName = time() . '_' . basename($_FILES['image']['name']);
                $imagePath = $uploadDir . $imageName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $image = 'assets/images/' . $imageName;
                } else {
                    $statusMsg = "Error uploading image.";
                }
            } else {
                $statusMsg = "Please upload an image.";
            }

            if (!$statusMsg) {
                $stmt = $conn->prepare("INSERT INTO services (name, image, price) VALUES (?, ?, ?)");
                $stmt->bind_param("ssd", $name, $image, $price);
                if ($stmt->execute()) {
                    $statusMsg = "Service added successfully!";
                } else {
                    $statusMsg = "Error adding service: " . $conn->error;
                }
            }
        }
        // Update Service
        elseif ($_POST['action'] === 'update_service') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            // Handle image upload (optional)
            $image = filter_input(INPUT_POST, 'existing_image', FILTER_SANITIZE_STRING);
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/';
                $imageName = time() . '_' . basename($_FILES['image']['name']);
                $imagePath = $uploadDir . $imageName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $image = 'assets/images/' . $imageName;
                } else {
                    $statusMsg = "Error uploading image.";
                }
            }

            if (!$statusMsg) {
                $stmt = $conn->prepare("UPDATE services SET name = ?, image = ?, price = ? WHERE id = ?");
                $stmt->bind_param("ssdi", $name, $image, $price, $id);
                if ($stmt->execute()) {
                    $statusMsg = "Service updated successfully!";
                } else {
                    $statusMsg = "Error updating service: " . $conn->error;
                }
            }
        }
        // Delete Service
        elseif ($_POST['action'] === 'delete_service') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $statusMsg = "Service deleted successfully!";
            } else {
                $statusMsg = "Error deleting service: " . $conn->error;
            }
        }
    }
}

// Module 3: Data Fetching
// Fetch all services
$services = [];
$result = $conn->query("SELECT * FROM services ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch orders for each service
$orders = [];
foreach ($services as $service) {
    $stmt = $conn->prepare("SELECT id, user_id, full_name, email, phone, notes, status, created_at FROM orders WHERE service_id = ? ORDER BY created_at DESC");
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
    <title>Manage Services | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .service-table, .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .service-table th, .service-table td, .order-table th, .order-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .service-table th, .order-table th {
            background: #3498db;
            color: #fff;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .form-container input, .form-container textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container button {
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
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
        .service-image {
            width: 100px;
            height: auto;
            border-radius: 5px;
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
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php" class="active"><i class="fas fa-cogs"></i> Services</a>
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
                    <h1><i class="fas fa-cogs"></i> Manage Services</h1>
                    <p>Manage printing and multimedia services for Mungu Ni Dawa.</p>
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

            <!-- Module 6: Add Service Form -->
            <div class="form-container">
                <h2>Add New Service</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_service">
                    <input type="text" name="name" placeholder="Service Name" required>
                    <input type="file" name="image" accept="image/*" required>
                    <input type="number" name="price" placeholder="Price (MWK)" step="0.01" min="0" required>
                    <button type="submit">Add Service</button>
                </form>
            </div>

            <!-- Module 7: Services Table -->
            <table class="service-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Image</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo $service['id']; ?></td>
                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                            <td><img src="/smart-printing-system/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image"></td>
                            <td><?php echo formatCurrency($service['price']); ?></td>
                            <td class="action-links">
                                <a href="#edit-<?php echo $service['id']; ?>" onclick="showEditForm(<?php echo $service['id']; ?>)">Edit</a>
                                <a href="#" class="delete" onclick="if(confirm('Are you sure?')){deleteService(<?php echo $service['id']; ?>)}">Delete</a>
                            </td>
                        </tr>
                        <!-- Edit Form (Hidden) -->
                        <tr id="edit-<?php echo $service['id']; ?>" style="display:none;">
                            <td colspan="5">
                                <div class="form-container">
                                    <h2>Edit Service</h2>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="update_service">
                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($service['image']); ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                                        <input type="file" name="image" accept="image/*">
                                        <p>Current Image: <img src="/smart-printing-system/<?php echo htmlspecialchars($service['image']); ?>" alt="Current Image" class="service-image"></p>
                                        <input type="number" name="price" value="<?php echo $service['price']; ?>" step="0.01" min="0" required>
                                        <button type="submit">Update Service</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Module 8: Orders Table -->
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders[$service['id']])): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No orders for this service.</td>
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
                                    <td class="action-links">
                                        <a href="#" class="delete" onclick="if(confirm('Are you sure?')){deleteOrder(<?php echo $order['id']; ?>)}">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Module 9: JavaScript -->
    <script>
        function showEditForm(id) {
            document.querySelectorAll('tr[id^="edit-"]').forEach(tr => tr.style.display = 'none');
            document.getElementById(`edit-${id}`).style.display = 'table-row';
        }

        function deleteService(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete_service"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function deleteOrder(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete_order"><input type="hidden" name="id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
