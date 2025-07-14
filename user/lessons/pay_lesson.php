<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connect.php';
include '../includes/header.php';

if (!isset($_SESSION['users'])) {
    header("Location: /smart-printing-system/admin/login.php");
    exit();
}

if (!isset($_GET['lesson_id']) || !is_numeric($_GET['lesson_id'])) {
    echo "Invalid lesson ID.";
    exit();
}

$lesson_id = intval($_GET['lesson_id']);
$email = $_SESSION['users']['email'];
$name = $_SESSION['users']['name'] ?? 'User';

// Get lesson details
$stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    echo "Lesson not found.";
    exit();
}

$title = $lesson['title'];
$fee_type = $lesson['fee_type']; // Free or Paid
$amount = floatval($lesson['fee']); // Ensure numeric

$tx_ref = "TX_" . uniqid() . "_" . time();

// Save transaction for verification later
$save = $conn->prepare("INSERT INTO payments (transaction_id, email, lesson_id, amount, status) VALUES (?, ?, ?, ?, 'pending')");
$save->bind_param("ssii", $tx_ref, $email, $lesson_id, $amount);
$save->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Pay for Lesson | Smart Printing</title>
  <script src="https://in.paychangu.com/js/popup.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('/smart-printing-system/assets/images/Background.jpeg') no-repeat center center fixed;
      background-size: cover;
      color: #333;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    main {
      flex-grow: 1;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-container {
      background: #ffffff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
      margin: 50px auto;
    }
    .auth-container h2 {
      margin-bottom: 24px;
      color: #0a3d62;
      font-size: 1.8rem;
    }
    .auth-container p {
      margin-bottom: 20px;
      font-size: 1.1rem;
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
      margin-top: 10px;
      transition: background 0.3s ease;
    }
    .auth-container button:hover {
      background-color: #064173;
    }
  </style>
</head>
<body>
  <main>
    <div class="auth-container">
      <h2>Pay for Lesson</h2>
      <p><strong>Lesson:</strong> <?= htmlspecialchars($title) ?></p>
      <p><strong>Amount:</strong> MWK <?= number_format($amount) ?></p>
      <div id="wrapper"></div>
      <button type="button" onclick="makePayment()">Pay Now</button>
    </div>
  </main>

  <script>
    function makePayment() {
      PaychanguCheckout({
        public_key: "PUB-TEST-oE03zCv1gfBpYxE67GtCIfiiC3gPJ7I0", // Use live key in production
        tx_ref: "<?= $tx_ref ?>",
        amount: <?= $amount ?>,
        currency: "MWK",
        callback_url: "https://2b05bb958f64.ngrok-free.app/smart-printing-system/user/lessons/verify_transaction.php",
        return_url: "https://2b05bb958f64.ngrok-free.app/smart-printing-system/user/lessons/payment_success.php",
        customer: {
          email: "<?= $email ?>",
          first_name: "<?= explode(' ', $name)[0] ?>",
          last_name: "<?= explode(' ', $name)[1] ?? '' ?>"
        },
        customization: {
          title: "Lesson Payment - <?= addslashes($title) ?>",
          description: "Payment for <?= addslashes($title) ?> lesson"
        },
        meta: {
          lesson_id: "<?= $lesson_id ?>",
          response: "Pending"
        }
      });
    }
  </script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
