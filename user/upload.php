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
  $contact = $_SESSION['users']['phone'];
  $user_id = $_SESSION['users']['id'];
  $service_id = null; // For manual uploads not tied to a service

  $file = $_FILES['document'];
  $allowedTypes = ['pdf', 'docx', 'jpg', 'png'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

  // === Price Lookup ===
  $priceQuery = $conn->prepare("SELECT price FROM prices WHERE page_size = ?");
  $priceQuery->bind_param("s", $size);
  $priceQuery->execute();
  $priceResult = $priceQuery->get_result();

  if ($priceResult->num_rows === 0) {
    $error = "Price for selected size not found.";
  } else {
    $priceData = $priceResult->fetch_assoc();
    $pricePerCopy = floatval($priceData['price']);
    $totalAmount = $pricePerCopy * $copies;

    if (!in_array($ext, $allowedTypes)) {
      $error = "Only PDF, DOCX, JPG, or PNG files are allowed.";
    } else {
      // Sanitize and generate filename
      $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
      $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
      $newFileName = 'print_' . uniqid() . '_' . substr($safeName, 0, 50) . '.' . $ext;
      $originalFileName = $originalName . '.' . $ext;

      // Set upload paths
      $uploadDir = 'C:/wamp64/www/smart-printing-system/uploads/notes/';
      $webPath = '/smart-printing-system/uploads/notes/' . $newFileName;

      // Create directory if it doesn't exist
      if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      $uploadPath = $uploadDir . $newFileName;

 // Inside the file upload success block, after move_uploaded_file()
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $status = 'Uploaded';
    
    $stmt = $conn->prepare("
        INSERT INTO transactions 
        (user_id, customer_name, customer_contact, reference_code, 
         file_name, original_filename, copies, color_type, page_size, 
         total_amount, service_id, amount, status) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "isssssisdddsd",
        $user_id, $name, $contact, $ref,
        $webPath, $originalFileName, $copies, $color,
        $size, $totalAmount, $service_id, $totalAmount, $status
    );

    if ($stmt->execute()) {
        $transaction_id = $conn->insert_id;
        $_SESSION['pay_for_document'] = $transaction_id;
        header("Location: pay_document.php?transaction_id=$transaction_id");
        exit();
    } else {
        $error = "Failed to save the transaction: " . $conn->error;
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
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
  
 <style>
    .error { color: red; margin: 10px 0; }
    .success { color: green; margin: 10px 0; font-weight: bold; }
    .auth-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    form input, form select { width: 100%; padding: 10px; margin: 8px 0; }
    button[type="submit"] { background: #0a3d62; color: white; padding: 10px 15px; border: none; cursor: pointer; }
  </style>
</head>
<body>

<div class="auth-container">
  <h2>📤 Upload Your Document</h2>

  <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success); ?></div>
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

