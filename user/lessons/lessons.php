<?php
session_start();
include '../includes/db_connect.php';
include '../includes/header.php';

// === Handle payment success message from PayChangu ===
if (isset($_GET['tx_ref'])) {
    $tx_ref = $_GET['tx_ref'];
    $secret = "SEC-TEST-pAOEgDVN5abHFnHkf5HCUkluBK01Pzi6";
    $verify_url = "https://api.paychangu.com/verify-payment/$tx_ref";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $verify_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer $secret"
        ]
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $statusMsg = "";
    if ($err) {
        $statusMsg = "❌ Could not verify payment. Please contact support.";
    } else {
        $data = json_decode($response, true);
        if (isset($data['data']['status']) && $data['data']['status'] === 'success') {
            $statusMsg = "✅ Payment successful! You can now access the course.";
        } else {
            $statusMsg = "⏳ Payment not successful yet. Status: " . ($data['data']['status'] ?? 'Unknown');
        }
    }
    echo "<div style='background:#fff;padding:15px;margin:20px auto;text-align:center;max-width:600px;border-radius:10px;font-weight:bold;color:#0a3d62;'>$statusMsg</div>";
}

// === Fetch all lessons from DB including image ===
$lessons = [];
$sql = "SELECT id, title, description, duration_weeks, fee_type, fee, image FROM lessons ORDER BY id DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lessons[] = $row;
    }
} else {
    echo "<p>Error loading lessons.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Smart Printing | Courses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('/smart-printing-system/assets/images/Background.jpeg') no-repeat center center fixed;
      background-size: cover;
      padding: 40px 20px;
      color: #333;
    }

    h1.section-title {
      text-align: center;
      color: #0a3d62;
      margin-bottom: 40px;
      background: rgba(255,255,255,0.85);
      padding: 15px 20px;
      border-radius: 10px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
      font-size: 1.4rem;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .course-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
      gap: 25px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .course-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.12);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      border: 1px solid #ddd;
    }
    .course-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .course-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-bottom: 1px solid #ddd;
    }

    .course-card-content {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .course-title {
      font-size: 1.3rem;
      color: #0a3d62;
      margin-bottom: 8px;
      font-weight: 700;
      flex-grow: 0;
    }

    .course-desc {
      flex-grow: 1;
      font-size: 0.95rem;
      color: #555;
      margin-bottom: 15px;
      line-height: 1.3;
    }

    .course-info {
      font-weight: 600;
      color: #0a3d62;
      margin-bottom: 5px;
    }

    .course-fee {
      margin-top: auto;
      font-weight: 700;
      color: #064173;
      font-size: 1.1rem;
    }

    .enroll-btn {
      margin-top: 15px;
      padding: 12px 15px;
      background-color: #0a3d62;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      transition: background-color 0.3s ease;
    }
    .enroll-btn:hover {
      background-color: #064173;
    }
  </style>
</head>
<body>

<h1 class="section-title">Available Courses</h1>

<div class="course-grid">
  <?php foreach ($lessons as $lesson): ?>
    <div class="course-card" onclick="location.href='lesson_details.php?lesson_id=<?= (int)$lesson['id'] ?>'">
      <img 
        src="/smart-printing-system/assets/images/<?= htmlspecialchars($lesson['image']) ?>" 
        alt="<?= htmlspecialchars($lesson['title']) ?>" 
        onerror="this.onerror=null;this.src='/smart-printing-system/assets/images/courses/default.jpg'">
      <div class="course-card-content">
        <div class="course-title"><?= htmlspecialchars($lesson['title']) ?></div>
        <div class="course-desc"><?= htmlspecialchars(mb_strimwidth($lesson['description'], 0, 100, '...')) ?></div>
        <div class="course-info">Duration: <?= htmlspecialchars($lesson['duration_weeks']) ?> week(s)</div>
        <div class="course-fee">
          <?= strtolower($lesson['fee_type']) === 'free' ? 'Free' : 'Paid MWK ' . number_format($lesson['fee'], 2) ?>
        </div>
        <a href="lesson_details.php?lesson_id=<?= (int)$lesson['id'] ?>" class="enroll-btn">View Course</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
