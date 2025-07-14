<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Restrict access to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include '../user/includes/db_connect.php';
include 'includes/functions.php';
include 'includes/load_settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="admin-wrapper">
    <!-- Sidebar (kept as provided) -->
    <div class="sidebar">
      <div class="logo">
      <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
      <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
      </div>
      <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
      <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
      <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
      <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
      <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
      <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
      <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
      <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php" style="color: red;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <header class="dashboard-header">
        <div class="header-content">
          <h1><i class="fas fa-chart-bar"></i> Admin Dashboard</h1>
          <p>Welcome to your Smart Printing admin panel, <?php echo htmlspecialchars($_SESSION['users']['username']); ?>!</p>
        </div>
        <div class="user-profile">
          <i class="fas fa-user-circle"></i>
          <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
        </div>
      </header>

      <?php
      $totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
      $totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
      $totalLessons = $conn->query("SELECT COUNT(*) AS count FROM lessons")->fetch_assoc()['count'];
      $totalPayments = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
      ?>

      <div class="stats-grid">
        <div class="stat-card users">
          <div class="stat-icon"><i class="fas fa-user"></i></div>
          <div class="stat-info">
            <h3>Total Users</h3>
            <p class="stat-value"><?php echo $totalUsers; ?></p>
            <span class="trend up"><i class="fas fa-arrow-up"></i> 12% from last month</span>
          </div>
        </div>
        <div class="stat-card orders">
          <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
          <div class="stat-info">
            <h3>Total Orders</h3>
            <p class="stat-value"><?php echo $totalOrders; ?></p>
            <span class="trend up"><i class="fas fa-arrow-up"></i> 8% from last month</span>
          </div>
        </div>
        <div class="stat-card lessons">
          <div class="stat-icon"><i class="fas fa-book"></i></div>
          <div class="stat-info">
            <h3>Total Lessons</h3>
            <p class="stat-value"><?php echo $totalLessons; ?></p>
            <span class="trend down"><i class="fas fa-arrow-down"></i> 3% from last month</span>
          </div>
        </div>
        <div class="stat-card payments">
          <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
          <div class="stat-info">
            <h3>Total Payments</h3>
            <p class="stat-value"><?php echo formatCurrency($totalPayments); ?></p>
            <span class="trend up"><i class="fas fa-arrow-up"></i> 15% from last month</span>
          </div>
        </div>
      </div>

      <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
          <a href="manage_users.php" class="action-btn"><i class="fas fa-users"></i> Manage Users</a>
          <a href="manage_services.php" class="action-btn"><i class="fas fa-cogs"></i> Manage Services</a>
          <a href="manage_lessons.php" class="action-btn"><i class="fas fa-book"></i> Manage Lessons</a>
          <a href="lessons/class.php" class="action-btn"><i class="fas fa-chart-line"></i> Classes</a>
          <a href="manage_transactions.php" class="action-btn"><i class="fas fa-credit-card"></i> Transactions</a>
          <a href="reports.php" class="action-btn"><i class="fas fa-chart-line"></i> Reports</a>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/footer.php'; ?>
</body>
</html>
