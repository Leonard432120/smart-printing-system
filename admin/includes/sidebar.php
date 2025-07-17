<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['users']) || $_SESSION['users']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  
  <div class="admin-wrapper">
    <div class="sidebar">
      <div class="logo">
        <img src="/smart-printing-system/assets/images/MND.jpeg" alt="Logo" class="logo-img">
        <span>Mungu Ni Dawa</span>
      </div>
      <a href="/smart-printing-system/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="/smart-printing-system/admin/manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
      <a href="/smart-printing-system/admin/manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
      <a href="/smart-printing-system/admin/manage_services.php"><i class="fas fa-cogs"></i> Services</a>
      <a href="/smart-printing-system/admin/manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
      <a href="/smart-printing-system/admin/manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
      <a href="/smart-printing-system/admin/manage_users.php"><i class="fas fa-users"></i> Users</a>
       <a href="lessons/class.php"><i class="fas fa-chart-line"></i> Classes</a>
      <a href="/smart-printing-system/admin/reports.php"><i class="fas fa-chart-line"></i> Reports</a>
      <a href="/smart-printing-system/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="/smart-printing-system/logout.php" style="color: red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
