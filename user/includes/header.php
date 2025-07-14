<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smart Printing System</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
  <link rel="icon" href="/smart-printing-system/assets/images/ponje.png" />
</head>
<body>

<header class="site-header">
  <div class="nav-container">
    <div class="logo">
      <a href="/smart-printing-system/user/index.php">
        <img src="/smart-printing-system/assets/images/MND.jpeg" alt="Logo" class="logo-img">
        <span>Mungu Ni Dawa</span>
      </a>
    </div>

    <nav class="navbar">
      <a href="/smart-printing-system/user/index.php">Home</a>
      <a href="/smart-printing-system/user/contact.php">Contact</a>

      <?php if (isset($_SESSION['users'])): ?>
        <a href="/smart-printing-system/user/history.php">History</a>
        <a href="/smart-printing-system/user/services.php">Services</a>
        <a href="/smart-printing-system/user/notifications.php">Notifications</a>
        <a href="/smart-printing-system/admin/logout.php" class="logout-btn" style="color: red;">Logout</a>
      <?php else: ?>
        <a href="/smart-printing-system/admin/login.php" class="login-btn">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
