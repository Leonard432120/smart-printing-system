<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../user/includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = strtolower(trim($_POST['role']));

    // Admin verification code - change to your own secret
    $admin_verification_code = "SuperSecretAdminCode123";

    if ($role === 'admin') {
        $provided_code = trim($_POST['admin_code'] ?? '');

        if ($provided_code !== $admin_verification_code) {
            $error = "Invalid admin verification code. You cannot register as admin.";
        }
    }

    if (empty($error)) {
        // Check if username or email already exists
        $checkSql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Insert new user
            $sql = "INSERT INTO users (name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $name, $username, $email, $phone, $password, $role);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin.css" />
  <style>
    /* Optional quick inline styles for form layout */
    .auth-container {
      max-width: 400px;
      margin: 60px auto;
      padding: 25px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .auth-container h2 {
      text-align: center;
      color: #0a3d62;
      margin-bottom: 20px;
    }
    .auth-container input, .auth-container select {
      width: 100%;
      padding: 12px 10px;
      margin-bottom: 15px;
      border: 1.8px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
    }
    .auth-container button {
      width: 100%;
      padding: 14px;
      background-color: #0a3d62;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    .auth-container button:hover {
      background-color: #06406e;
    }
    .error-message {
      color: red;
      margin-bottom: 15px;
      text-align: center;
    }
    .auth-container p {
      text-align: center;
      font-size: 0.9rem;
    }
    .auth-container a {
      color: #0a3d62;
      font-weight: 600;
      text-decoration: none;
    }
    .auth-container a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="auth-container">
  <h2>Create Account</h2>

  <?php if (!empty($error)) : ?>
    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <input type="text" name="name" placeholder="Full Name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
    <input type="text" name="username" placeholder="Username (e.g., Leonard123)" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    <input type="text" name="phone" placeholder="Phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
    <input type="password" name="password" placeholder="Password" required>

    <select name="role" id="role" required>
      <option value="" disabled <?php if(empty($_POST['role'])) echo 'selected'; ?>>Select Role</option>
      <option value="user" <?php if(isset($_POST['role']) && $_POST['role'] === 'user') echo 'selected'; ?>>User</option>
      <option value="staff" <?php if(isset($_POST['role']) && $_POST['role'] === 'staff') echo 'selected'; ?>>Staff</option>
      <option value="admin" <?php if(isset($_POST['role']) && $_POST['role'] === 'admin') echo 'selected'; ?>>Admin</option>
    </select>

    <div id="adminCodeDiv" style="display: <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'block' : 'none'; ?>;">
      <input type="text" name="admin_code" placeholder="Enter Admin Verification Code" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'required' : ''; ?>>
    </div>

    <button type="submit">Register</button>
  </form>

  <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<script>
  const roleSelect = document.getElementById('role');
  const adminCodeDiv = document.getElementById('adminCodeDiv');

  roleSelect.addEventListener('change', () => {
    if (roleSelect.value === 'admin') {
      adminCodeDiv.style.display = 'block';
      adminCodeDiv.querySelector('input').setAttribute('required', 'required');
    } else {
      adminCodeDiv.style.display = 'none';
      adminCodeDiv.querySelector('input').removeAttribute('required');
      adminCodeDiv.querySelector('input').value = '';
    }
  });
</script>

</body>
</html>
