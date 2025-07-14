<?php
session_start();
include '../includes/db_connect.php';
include '../includes/header.php';
include 'includes/load_settings.php';


// Restrict to admin only
if (!isset($_SESSION['users']) || strtolower($_SESSION['users']['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Class Activities | Admin Panel</title>
  <link rel="stylesheet" href="/smart-printing-system/admin/assets/css/upload_pages.css">
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
<div>
      <div class="logo">
      <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="logo-img">
      <span><?php echo htmlspecialchars($settings['business_name']); ?></span>
      </div>
<div class="container">
  <h2>📚 Class Activities</h2>

  <div class="cards-grid">
    <div class="card">
      <h3>📝 Upload Notes</h3>
      <p>Add downloadable class notes for students enrolled in lessons.</p>
      <a href="upload_note.php">Go to Upload Notes</a>
    </div>

    <div class="card">
      <h3>📚 Upload Books</h3>
      <p>Provide extra learning resources by uploading PDF books.</p>
      <a href="upload_book.php">Go to Upload Books</a>
    </div>

    <div class="card">
      <h3>📅 Schedule Class</h3>
      <p>Set live class schedules or post meeting links and times.</p>
      <a href="schedule_class.php">Go to Schedule Class</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
