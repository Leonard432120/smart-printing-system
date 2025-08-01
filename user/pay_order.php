<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

if (!isset($_SESSION['users'])) {
    header("Location: ../admin/login.php");
    exit();
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo "Invalid order ID.";
    exit();
}

$order_id = intval($_GET['order_id']);
$email = $_SESSION['users']['email'];
$name = $_SESSION['users']['name'];

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, s.name AS service_name, s.price FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found.";
    exit();
}

$service_name = $order['service_name'];
$amount = floatval($order['price']);
$tx_ref = "ORD_" . uniqid() . "_" . time();

// Save transaction
$save = $conn->prepare("INSERT INTO payments (transaction_id, email, order_id, amount, status, payment_date) VALUES (?, ?, ?, ?, 'pending', NOW())");
$save->bind_param("ssii", $tx_ref, $email, $order_id, $amount);
$save->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pay for Order | Smart Printing</title>
  <script src="https://in.paychangu.com/js/popup.js"></script> <!-- ✅ Correct script -->
  <style>
    body, html {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('/smart-printing-system/assets/images/Background.jpeg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }

    .auth-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      max-width: 420px;
      width: 100%;
      text-align: center;
    }

    .auth-container h2 {
      margin-bottom: 20px;
      color: #0a3d62;
      font-size: 1.6rem;
    }

    .auth-container p {
      font-size: 1.1rem;
      margin-bottom: 15px;
    }

    #wrapper {
      margin-top: 20px;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #0a3d62;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #064173;
    }
  </style>
</head>
<body>

  <main>
    <div class="auth-container">
      <h2>Pay for Service</h2>
      <p><strong>Service:</strong> <?= htmlspecialchars($service_name) ?></p>
      <p><strong>Amount:</strong> MWK <?= number_format($amount) ?></p>

      <!-- ✅ Required wrapper for the popup -->
      <div id="wrapper"></div>

      <button type="button" onclick="makePayment()">Pay Now</button>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script>
    function makePayment(){
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
          title: "Payment for <?= htmlspecialchars($service_name) ?>",
          description: "Smart Printing Service Payment"
        },
        meta: {
          order_id: <?= $order_id ?>,
          source: "smart_printing_order"
        }
      });
    }
  </script>
</body>
</html>
