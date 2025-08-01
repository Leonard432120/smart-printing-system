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

// Fetch data for dashboard
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'];
$totalLessons = $conn->query("SELECT COUNT(*) AS count FROM lessons")->fetch_assoc()['count'];
$totalPayments = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE status = 'success'")->fetch_assoc()['total'] ?? 0;

// Fetch recent users
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fb;
      color: #333;
      margin: 0;
      padding: 0;
    }
    
    .admin-wrapper {
      display: flex;
      min-height: 100vh;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: 250px;
      background: linear-gradient(180deg, #2c3e50, #1a2530);
      color: white;
      padding: 20px 0;
      transition: all 0.3s;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    
    .logo {
      display: flex;
      align-items: center;
      padding: 0 20px 20px;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .logo-img {
      width: 40px;
      height: 40px;
      margin-right: 10px;
      border-radius: 50%;
    }
    
    .sidebar a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #b3b3b3;
      text-decoration: none;
      transition: all 0.3s;
      font-size: 14px;
    }
    
    .sidebar a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    .sidebar a:hover, .sidebar a.active {
      background: rgba(255,255,255,0.1);
      color: white;
      border-left: 3px solid var(--primary);
    }
    
    .sidebar a.active {
      background: rgba(255,255,255,0.05);
    }
    
    /* Main Content Styles */
    .main-content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }
    
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #eee;
    }
    
    .header-content h1 {
      margin: 0;
      color: var(--dark);
      font-size: 24px;
    }
    
    .header-content p {
      margin: 5px 0 0;
      color: #6c757d;
      font-size: 14px;
    }
    
    .user-profile {
      display: flex;
      align-items: center;
      background: white;
      padding: 8px 15px;
      border-radius: 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .user-profile i {
      font-size: 20px;
      margin-right: 10px;
      color: var(--primary);
    }
    
    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      transition: transform 0.3s;
      border-left: 4px solid;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card.users { border-color: var(--primary); }
    .stat-card.orders { border-color: var(--success); }
    .stat-card.lessons { border-color: var(--warning); }
    .stat-card.payments { border-color: var(--danger); }
    
    .stat-card .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      margin-bottom: 15px;
    }
    
    .stat-card.users .stat-icon { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
    .stat-card.orders .stat-icon { background: rgba(76, 201, 240, 0.1); color: var(--success); }
    .stat-card.lessons .stat-icon { background: rgba(248, 150, 30, 0.1); color: var(--warning); }
    .stat-card.payments .stat-icon { background: rgba(247, 37, 133, 0.1); color: var(--danger); }
    
    .stat-card h3 {
      margin: 0 0 5px;
      font-size: 16px;
      color: #6c757d;
      font-weight: 500;
    }
    
    .stat-card .stat-value {
      font-size: 24px;
      font-weight: 600;
      margin: 0 0 5px;
      color: var(--dark);
    }
    
    .trend {
      font-size: 12px;
      display: flex;
      align-items: center;
    }
    
    .trend i {
      margin-right: 5px;
    }
    
    .trend.up { color: #28a745; }
    .trend.down { color: #dc3545; }
    
    /* User Management Section */
    .user-management-section {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .section-header h2 {
      margin: 0;
      font-size: 18px;
      color: var(--dark);
    }
    
    .search-box {
      position: relative;
      width: 250px;
    }
    
    .search-box input {
      width: 100%;
      padding: 8px 15px 8px 35px;
      border: 1px solid #ddd;
      border-radius: 20px;
      font-size: 14px;
    }
    
    .search-box i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }
    
    .user-table {
      display: grid;
      grid-template-columns: 50px 1fr 2fr 100px;
      font-size: 14px;
    }
    
    .table-header {
      font-weight: 600;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
      color: var(--dark);
    }
    
    .table-row {
      padding: 12px 0;
      border-bottom: 1px solid #f5f5f5;
      display: contents;
    }
    
    .table-row > div {
      padding: 8px 0;
      align-self: center;
    }
    
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .badge.active { background: #d4edda; color: #155724; }
    .badge.delayed { background: #fff3cd; color: #856404; }
    .badge.removed { background: #f8d7da; color: #721c24; }
    
    /* Alert Section */
    .alert-section {
      margin-bottom: 30px;
    }
    
    .alert {
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    
    .alert i {
      margin-right: 10px;
      font-size: 18px;
    }
    
    .alert.warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
    .alert.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .alert.info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
    .alert.danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    
    /* Recent Activity Section */
    .recent-activity {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    
    .recent-activity h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: var(--dark);
      display: flex;
      align-items: center;
    }
    
    .recent-activity h3 i {
      margin-right: 10px;
      color: var(--primary);
    }
    
    .recent-activity > p {
      margin: 0 0 15px;
      color: #6c757d;
      font-size: 14px;
    }
    
    .activity-item {
      padding: 10px 0;
      border-bottom: 1px solid #f5f5f5;
    }
    
    .activity-date {
      font-size: 12px;
      color: #6c757d;
      margin-bottom: 5px;
    }
    
    .activity-content {
      font-size: 14px;
    }
    
    .loading-indicator {
      display: flex;
      align-items: center;
      color: #6c757d;
      font-size: 14px;
      padding: 15px 0 5px;
    }
    
    .loading-indicator i {
      margin-right: 8px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Components Section */
    .components-section {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    
    .components-section h3 {
      margin: 0 0 20px;
      font-size: 18px;
      color: var(--dark);
    }
    
    .component-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .component-card {
      border: 1px solid #eee;
      border-radius: 6px;
      padding: 15px;
      text-align: center;
      transition: all 0.3s;
    }
    
    .component-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .component-card.default { border-top: 3px solid #6c757d; }
    .component-card.success { border-top: 3px solid #28a745; }
    .component-card.info { border-top: 3px solid #17a2b8; }
    .component-card.warning { border-top: 3px solid #ffc107; }
    .component-card.danger { border-top: 3px solid #dc3545; }
    
    .component-header {
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 14px;
    }
    
    .component-content {
      font-size: 24px;
      font-weight: 600;
    }
    
    .documentation-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .documentation-link a {
      color: var(--primary);
      text-decoration: none;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
    }
    
    .documentation-link a i {
      margin-right: 5px;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
      .admin-wrapper {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
        padding: 10px 0;
      }
      
      .sidebar a {
        padding: 10px 15px;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .user-table {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .table-header {
        display: none;
      }
      
      .table-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 6px;
        margin-bottom: 10px;
      }
      
      .table-row > div::before {
        content: attr(data-label);
        font-weight: 600;
        display: block;
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
      }
    }
    .badge.active { background: #d4edda; color: #155724; }  /* Admin */
.badge.success { background: #cce5ff; color: #004085; } /* Student */
.badge.info { background: #e2e3e5; color: #383d41; }    /* Teacher */
.badge.warning { background: #fff3cd; color: #856404; } /* Others */
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
      <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
      <a href="manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
      <a href="manage_services.php"><i class="fas fa-cogs"></i> Services</a>
      <a href="/smart-printing-system/admin/manage_uploads.php"><i class="fas fa-print"></i> Printing</a>
      <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
      <a href="manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
      <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
      <a href="lessons/class.php"><i class="fas fa-chart-line"></i> Classes</a>
      <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
      <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php" style="color: #ff6b6b;" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

      <!-- Stats Grid -->
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

      <!-- User Management Section -->
<div class="user-management-section">
    <div class="section-header">
        <h2><i class="fas fa-users"></i> Users</h2>
        <div class="search-box">
            <input type="text" placeholder="Search...">
            <i class="fas fa-search"></i>
        </div>
    </div>
    
    <div class="user-table">
        <div class="table-header">
            <div class="col-id">ID</div>
            <div class="col-username">Username</div>
            <div class="col-email">Email</div>
            <div class="col-role">Role</div>  <!-- Changed from Status to Role -->
        </div>
        
        <?php 
        $recentUsers = $conn->query("SELECT id, username, email, role FROM users ORDER BY created_at DESC LIMIT 5");
        while($user = $recentUsers->fetch_assoc()): 
            $roleClass = match(strtolower($user['role'])) {
                'admin' => 'active',
                'student' => 'success',
                'teacher' => 'info',
                default => 'warning'
            };
        ?>
        <div class="table-row">
            <div class="col-id" data-label="ID"><?php echo $user['id']; ?></div>
            <div class="col-username" data-label="Username"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="col-email" data-label="Email"><?php echo htmlspecialchars($user['email']); ?></div>
            <div class="col-role" data-label="Role">
                <span class="badge <?php echo $roleClass; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

      <!-- Alert Section -->
      <div class="alert-section">
        <div class="alert warning">
          <i class="fas fa-exclamation-triangle"></i>
          <div class="alert-content">
            <strong>Warning:</strong> Best check yo self, you're not looking too good.
          </div>
        </div>
        
        <div class="alert success">
          <i class="fas fa-check-circle"></i>
          <div class="alert-content">
            <strong>Success:</strong> You successfully read this important alert message.
          </div>
        </div>
        
        <div class="alert info">
          <i class="fas fa-info-circle"></i>
          <div class="alert-content">
            <strong>Info:</strong> This alert needs your attention, but it's not super important.
          </div>
        </div>
        
        <div class="alert danger">
          <i class="fas fa-times-circle"></i>
          <div class="alert-content">
            <strong>Danger:</strong> Change this and that and try again.
          </div>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div class="recent-activity">
        <h3><i class="fas fa-clock"></i> Recent Activity</h3>
        <p>System activities that happened recently</p>
        
        <div class="activity-item">
          <div class="activity-date">2025-08-01</div>
          <div class="activity-content">New user "Alice" registered</div>
        </div>
        
        <div class="activity-item">
          <div class="activity-date">2025-07-31</div>
          <div class="activity-content">Printing service updated</div>
        </div>
        
        <div class="activity-item">
          <div class="activity-date">2025-07-30</div>
          <div class="activity-content">System maintenance completed</div>
        </div>
        
        <div class="loading-indicator">
          <i class="fas fa-spinner fa-spin"></i> Loading more activities...
        </div>
      </div>

      <!-- Components Section -->
      <div class="components-section">
        <h3>Dashboard Components</h3>
        
        <div class="component-grid">
          <div class="component-card default">
            <div class="component-header">Default</div>
            <div class="component-content">1</div>
          </div>
          
          <div class="component-card success">
            <div class="component-header">Success</div>
            <div class="component-content">Dropdown <i class="fas fa-caret-down"></i></div>
          </div>
          
          <div class="component-card info">
            <div class="component-header">Info</div>
            <div class="component-content">2</div>
          </div>
          
          <div class="component-card warning">
            <div class="component-header">Warning</div>
            <div class="component-content"><i class="fas fa-exclamation"></i></div>
          </div>
          
          <div class="component-card danger">
            <div class="component-header">Danger</div>
            <div class="component-content"><i class="fas fa-times"></i></div>
          </div>
        </div>
        
        <div class="documentation-link">
          <a href="#"><i class="fas fa-book"></i> For more components please checkout the documentation</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>