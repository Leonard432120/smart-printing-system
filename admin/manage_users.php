<?php
// Module 1: Session and Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
        // Add User
        if ($_POST['action'] === 'add_user') {
            $name = htmlspecialchars(trim($_POST['name']));
            $username = htmlspecialchars(trim($_POST['username']));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password']; // Raw password for hashing
            $role = htmlspecialchars(trim($_POST['role']));

            $validRoles = ['admin', 'staff', 'user'];

            // Validate inputs
            if (empty($name)) {
                $statusMsg = "Full Name is required.";
            } elseif (empty($username)) {
                $statusMsg = "Username is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $statusMsg = "Invalid email format.";
            } elseif (strlen($password) < 6) {
                $statusMsg = "Password must be at least 6 characters.";
            } elseif (!in_array($role, $validRoles)) {
                $statusMsg = "Invalid role selected.";
            } else {
                // Check for duplicate username or email
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $statusMsg = "Username or email already exists.";
                } else {
                    // Hash password and insert user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);
                    if ($stmt->execute()) {
                        $statusMsg = "User added successfully!";
                    } else {
                        $statusMsg = "Error adding user: " . $conn->error;
                    }
                }
            }
        }
        // Update User
        elseif ($_POST['action'] === 'update_user') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $username = htmlspecialchars(trim($_POST['username']));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $role = htmlspecialchars(trim($_POST['role']));
            $password = $_POST['password'] ?? ''; // Optional password update

            $validRoles = ['admin', 'staff', 'user'];

            // Validate inputs
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $statusMsg = "Invalid email format.";
            } elseif (!in_array($role, $validRoles)) {
                $statusMsg = "Invalid role selected.";
            } elseif (empty($username)) {
                $statusMsg = "Username is required.";
            } else {
                // Check for duplicate username or email (excluding current user)
                $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->bind_param("ssi", $username, $email, $id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $statusMsg = "Username or email already exists.";
                } else {
                    if (!empty($password)) {
                        // Update with new password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $username, $email, $hashedPassword, $role, $id);
                    } else {
                        // Update without changing password
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $username, $email, $role, $id);
                    }
                    if ($stmt->execute()) {
                        $statusMsg = "User updated successfully!";
                    } else {
                        $statusMsg = "Error updating user: " . $conn->error;
                    }
                }
            }
        }
        // Delete User
        elseif ($_POST['action'] === 'delete_user') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            // Prevent deleting the current admin
            if ($id == $_SESSION['users']['id']) {
                $statusMsg = "Cannot delete your own account.";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $statusMsg = "User deleted successfully!";
                } else {
                    $statusMsg = "Error deleting user: " . $conn->error;
                }
            }
        }
    }
}

// Module 3: Data Fetching
$users = [];
$result = $conn->query("
    SELECT u.id, u.name, u.username, u.email, u.role, u.created_at,
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
           (SELECT COUNT(*) FROM enrollments e WHERE e.email = u.email) AS enrollment_count
    FROM users u
    ORDER BY u.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Users | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .user-table th {
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
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
            <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
            <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
            </div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
            <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
            <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
            <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-content">
                    <h1><i class="fas fa-users"></i> Manage Users</h1>
                    <p>Manage user accounts for Mungu Ni Dawa system.</p>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
                </div>
            </header>

            <?php if (!empty($statusMsg)): ?>
                <div class="status-msg <?php echo strpos($statusMsg, 'success') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($statusMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="form-container">
                <h2>Add New User</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user" />
                    <input type="text" name="name" placeholder="Full Name" required />
                    <input type="text" name="username" placeholder="Username" required />
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="password" placeholder="Password (min 6 characters)" required />
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="user">User</option>
                    </select>
                    <button type="submit">Add User</button>
                </form>
            </div>

            <!-- Users Table -->
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Enrollments</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;">No users available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo $user['order_count']; ?></td>
                                <td><?php echo $user['enrollment_count']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td class="action-links">
                                    <a href="#edit-<?php echo $user['id']; ?>" onclick="showEditForm(<?php echo $user['id']; ?>)">Edit</a>
                                    <a href="#" class="delete" onclick="if(confirm('Are you sure?')){deleteUser(<?php echo $user['id']; ?>);}">Delete</a>
                                </td>
                            </tr>
                            <!-- Edit Form (Hidden) -->
                            <tr id="edit-<?php echo $user['id']; ?>" style="display:none;">
                                <td colspan="9">
                                    <div class="form-container">
                                        <h2>Edit User</h2>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_user" />
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>" />
                                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required />
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
                                            <input type="password" name="password" placeholder="New Password (optional)" />
                                            <select name="role" required>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            </select>
                                            <button type="submit">Update User</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function showEditForm(id) {
            document.querySelectorAll('tr[id^="edit-"]').forEach(tr => tr.style.display = 'none');
            document.getElementById(`edit-${id}`).style.display = 'table-row';
            window.scrollTo(0, document.getElementById(`edit-${id}`).offsetTop - 50);
        }

        function deleteUser(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete_user" />
                              <input type="hidden" name="id" value="${id}" />`;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
