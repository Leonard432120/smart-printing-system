<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['users'])) {
  header("Location: ../admin/login.php");
  exit();
}

include 'includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Our Services | Mungu Ni Dawa</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/style.css" />
</head>
<body>

<?php include 'includes/header.php'; ?>

<section class="services-section">
  <div class="overlay">
    <div class="container">
      <h2 class="section-title">Our Services</h2>
      <div class="card-grid">
        <?php
        // Fetch services from database
        $query = "SELECT * FROM services ORDER BY id DESC";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
          while ($service = mysqli_fetch_assoc($result)) {
            $serviceName = htmlspecialchars($service['name']);
            $imagePath = htmlspecialchars($service['image']);
            $serviceId = (int)$service['id'];

            echo '<div class="card">';
            echo '<img src="/smart-printing-system/' . $imagePath . '" alt="' . $serviceName . '" style="width:100%; height:180px; object-fit:cover; border-radius:10px;">';
            echo '<div style="padding: 10px;">';
            echo '<h3>' . $serviceName . '</h3>';
            echo '<a href="order.php?service_id=' . $serviceId . '" class="btn">Order Now</a>';
            echo '</div>';
            echo '</div>';
          }
        } else {
          echo '<p>No services available at the moment.</p>';
        }
        ?>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>
