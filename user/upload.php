<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['users'])) {
  header("Location: ../admin/login.php");
  exit();
}

include 'includes/db_connect.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST['name']);
  $ref = trim($_POST['reference']);
  $copies = intval($_POST['copies']);
  $color = $_POST['color'];
  $size = $_POST['size'];
  $contact = $_SESSION['users']['phone']; // ✅ Get contact from session

  $file = $_FILES['document'];
  $allowedTypes = ['pdf', 'docx', 'jpg', 'png'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

  // === Calculate Price Per Copy Based on Size and Color ===
  $priceQuery = $conn->prepare("SELECT price FROM prices WHERE page_size = ? ");
  $priceQuery->bind_param("s", $size );
  $priceQuery->execute();
  $priceResult = $priceQuery->get_result();

  if ($priceResult->num_rows === 0) {
    $error = "Price for selected size and color not found.";
  } else {
    $priceData = $priceResult->fetch_assoc();
    $pricePerCopy = floatval($priceData['price']);
    $totalAmount = $pricePerCopy * $copies;

    if (!in_array($ext, $allowedTypes)) {
      $error = "Only PDF, DOCX, JPG, or PNG files are allowed.";
    } else {
      $newFileName = uniqid('print_', true) . '.' . $ext;
      $uploadDir = 'uploads/';
      $uploadPath = $uploadDir . $newFileName;

      if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $conn->prepare("
          INSERT INTO transactions 
            (user_id, customer_name, customer_contact, reference_code, file_name, copies, color_type, page_size, total_amount, status) 
          VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Uploaded')
        ");
        $stmt->bind_param(
          "issssissd",
          $_SESSION['users']['id'],
          $name,
          $contact,
          $ref,
          $newFileName,
          $copies,
          $color,
          $size,
          $totalAmount
        );

        if ($stmt->execute()) {
          $success = "🎉 Document uploaded successfully!";
        } else {
          $error = "Failed to save the transaction.";
        }
      } else {
        $error = "Upload failed. Try again.";
      }
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Upload Document | Smart Printing</title>
  <link rel="stylesheet" href="/smart-printing-system/assets/css/admin.css" />
</head>
<body>

<div class="auth-container">
  <h2>📤 Upload Your Document</h2>

  <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="color: green; font-weight: bold;"><?php echo $success; ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Your Full Name" required />
    
    <input type="text" name="reference" placeholder="Reference Code (e.g., DOC123)" required />

    <input type="number" name="copies" placeholder="Number of Copies" min="1" value="1" required />

    <select name="color" required>
      <option value="">Select Color Option</option>
      <option value="Color">Color</option>
      <option value="Black & White">Black & White</option>
    </select>

    <select name="size" required>
      <option value="">Select Page Size</option>
      <option value="A4">A4</option>
      <option value="A5">A5</option>
      <option value="A3">A3</option>
    </select>

    <input type="file" name="document" required />

    <button type="submit">Upload Document</button>
  </form>

  <p><a href="index.php">⬅ Back to Home</a></p>
</div>

</body>
</html>
