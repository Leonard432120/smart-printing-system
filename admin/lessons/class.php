<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Restrict to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/db_connect.php';
include '../includes/functions.php';

// Load settings safely
$settings = [
    'business_name' => 'Smart Printing',
    'logo_path' => '/smart-printing-system/assets/images/logo.png',
];
$settingsRes = $conn->query("SELECT business_name, logo_path FROM settings WHERE id = 1");
if ($settingsRes && $settingsRes->num_rows > 0) {
    $settingsRow = $settingsRes->fetch_assoc();
    $settings['business_name'] = $settingsRow['business_name'];
    $settings['logo_path'] = $settingsRow['logo_path'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Class Activities | Admin Panel</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin_style.css">
  <link rel="stylesheet" href="/smart-printing-system/admin/assets/css/upload_pages.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 25px;
      margin-top: 30px;
    }
    .card {
      background-color: #ffffff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    }
    .card h3 {
      margin-bottom: 12px;
      color: #0a3d62;
    }
    .card p {
      color: #555;
      font-size: 0.95rem;
    }
    .card a {
      display: inline-block;
      margin-top: 15px;
      background-color: #0a3d62;
      color: white;
      padding: 10px 18px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .card a:hover {
      background-color: #064173;
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
    <a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="../manage_lessons.php"><i class="fas fa-book"></i> Lessons</a>
    <a href="../manage_prices.php"><i class="fas fa-tag"></i> Prices</a>
    <a href="../manage_services.php"><i class="fas fa-cogs"></i> Services</a>
    <a href="../manage_students.php"><i class="fas fa-user-graduate"></i> Students</a>
    <a href="../manage_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
    <a href="../manage_users.php"><i class="fas fa-users"></i> Users</a>
    <a href="../reports.php"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="../logout.php" style="color: red;" onclick="return confirm('Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <header class="dashboard-header">
      <div class="header-content">
        <h1><i class="fas fa-chalkboard-teacher"></i> Class Activities</h1>
        <p>Manage classroom content for enrolled students</p>
      </div>
      <div class="user-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['users']['username']); ?></span>
      </div>
    </header>

    <div class="container">
      <div class="cards-grid">
        <div class="card">
          <h3>📝 Upload Notes</h3>
          <p>Add downloadable class notes for students enrolled in lessons.</p>
          <a href="upload_note.php">Go to Upload Notes</a>
        </div>
        <div class="card">
          <h3>📚 Upload Books</h3>
          <p>Provide extra learning resources by uploading PDF books.</p>
          <a href="upload_note.php">Go to Upload Books</a>
        </div>
        <div class="card">
          <h3>📅 Schedule Class</h3>
          <p>Set live class schedules or post meeting links and times.</p>
          <a href="schedule_class.php">Go to Schedule Class</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
