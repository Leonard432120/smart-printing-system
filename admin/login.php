<?php
ob_start(); // Start output buffering to allow header() redirects
session_start();
include '../user/includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['users'] = [
                'id'       => $user['id'],
                'name'     => $user['name'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'phone'    => $user['phone'],
                'role'     => $user['role']
            ];

            // Redirect based on role (case insensitive)
            if (strtolower($user['role']) === 'admin') {
                header("Location: /smart-printing-system/admin/dashboard.php");
                exit();
            } else {
                header("Location: /smart-printing-system/user/index.php");
                exit();
            }
            
        } else {
            $error = "❌ Invalid username or password.";
        }
    } else {
        $error = "❌ No user found with that username.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login | Smart Printing</title>
    <link rel="stylesheet" href="/smart-printing-system/assets/css/admin.css" />
</head>
<body>
<div class="auth-container">
    <h2>🔐 Login</h2>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="text" name="username" placeholder="Enter Username" required autofocus />
        <input type="password" name="password" placeholder="Enter Password" required />
        <button type="submit">Login</button>
    </form>

    <p>📌 No account yet? <a href="register.php">Register here</a>.</p>
</div>
</body>
</html>

<?php
ob_end_flush(); // Send output buffer
?>
