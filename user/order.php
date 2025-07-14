<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

if (!isset($_GET['service_id']) || !is_numeric($_GET['service_id'])) {
    header("Location: services.php");
    exit();
}

$service_id = (int)$_GET['service_id'];
$query = "SELECT * FROM services WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Service not found.</p>";
    exit();
}

$service = $result->fetch_assoc();
$user = $_SESSION['users'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Order Service | Smart Printing</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    main.main-content {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      background-image: url('/smart-printing-system/assets/images/Background.jpeg');
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }

    .auth-container {
      background: #ffffff;
      padding: 30px 25px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
      text-align: left;
    }

    .auth-container h2 {
      margin-bottom: 20px;
      color: #0a3d62;
      text-align: center;
      font-size: 1.8rem;
    }

    .auth-container img {
      width: 100%;
      max-height: 220px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .auth-container label {
      font-weight: 600;
      margin-bottom: 6px;
      display: block;
    }

    .auth-container input,
    .auth-container textarea {
      width: 100%;
      padding: 12px 14px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      margin-bottom: 16px;
    }

    .auth-container input:focus,
    .auth-container textarea:focus {
      border-color: #0a3d62;
      box-shadow: 0 0 0 2px rgba(10, 61, 98, 0.2);
      outline: none;
    }

    .auth-container button {
      width: 100%;
      padding: 12px;
      background-color: #0a3d62;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .auth-container button:hover {
      background-color: #064173;
    }

    @media (max-width: 480px) {
      .auth-container {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="main-content">
  <div class="auth-container">
    <h2>Order: <?= htmlspecialchars($service['name']) ?></h2>
    <img src="/smart-printing-system/<?= htmlspecialchars($service['image']) ?>" alt="Service Image" />

    <form action="process_order.php" method="POST">
      <input type="hidden" name="service_id" value="<?= $service_id ?>">

      <label for="full_name">Full Name</label>
      <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label for="phone">Phone</label>
      <input type="text" id="phone" name="phone" placeholder="Enter your phone number" required>

      <label for="notes">Additional Notes</label>
      <textarea id="notes" name="notes" rows="4" placeholder="Describe the service details (optional)..."></textarea>

      <button type="submit">Submit Order</button>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
