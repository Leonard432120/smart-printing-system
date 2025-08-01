<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

if (!isset($_GET['transaction_id']) || !is_numeric($_GET['transaction_id'])) {
    die("Invalid transaction ID.");
}

$transaction_id = intval($_GET['transaction_id']);
$user = $_SESSION['users'];
$email = $user['email'];
$name = $user['name'] ?? 'User';
$user_id = $user['id'];

// Fetch transaction details
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();

if (!$tx) {
    die("Transaction not found.");
}

$amount = floatval($tx['total_amount']);
$ref_code = $tx['reference_code'];
$copies = intval($tx['copies']);
$file = basename($tx['original_filename']);
$page_size = $tx['page_size'];
$tx_ref = "DOC_" . uniqid() . "_" . time();

// Save payment record
try {
    $save = $conn->prepare("
        INSERT INTO payments 
        (transaction_id, email, transaction_reference, user_id, amount, status, payment_date) 
        VALUES 
        (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $save->bind_param("issid", 
        $transaction_id,
        $email,
        $tx_ref,
        $user_id,
        $amount
    );
    
    if (!$save->execute()) {
        throw new Exception("Failed to save payment: " . $conn->error);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pay for Document | Smart Printing</title>
  <script src="https://in.paychangu.com/js/popup.js"></script>
  <style>
    body {
      font-family: Arial;
      background: #f8f9fa;
    }
    .auth-container {
      background: #fff;
      padding: 30px;
      max-width: 480px;
      margin: 80px auto;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 20px;
      color: #0a3d62;
    }
    p { margin: 8px 0; }
    button {
      padding: 12px 20px;
      margin-top: 20px;
      background: #0a3d62;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <h2>Pay for Document</h2>
    <p><strong>Reference:</strong> <?= htmlspecialchars($ref_code) ?></p>
    <p><strong>File:</strong> <?= htmlspecialchars($file) ?></p>
    <p><strong>Copies:</strong> <?= $copies ?></p>
    <p><strong>Page Size:</strong> <?= htmlspecialchars($page_size) ?></p>
    <p><strong>Amount:</strong> MWK <?= number_format($amount, 2) ?></p>
    <div id="wrapper"></div>
    <button onclick="makePayment()">Pay Now</button>
  </div>

<script>
function makePayment() {
  PaychanguCheckout({
    public_key: "PUB-TEST-oE03zCv1gfBpYxE67GtCIfiiC3gPJ7I0",
    tx_ref: "<?= $tx_ref ?>",
    amount: <?= $amount ?>,
    currency: "MWK",
    callback_url: "https://75a143094b02.ngrok-free.app/smart-printing-system/user/verify_transaction.php",
    return_url: "https://75a143094b02.ngrok-free.app/smart-printing-system/user/payment_success.php",
    customer: {
      email: "<?= $email ?>",
      first_name: "<?= explode(' ', $name)[0] ?>",
      last_name: "<?= explode(' ', $name)[1] ?? '' ?>"
    },
    customization: {
      title: "Document Printing Payment",
      description: "Payment for <?= addslashes($file) ?> (<?= $copies ?> copies)"
    },
    meta: {
      type: "document",
      transaction_id: "<?= $transaction_id ?>"
    }
  });
}
</script>
</body>
</html>