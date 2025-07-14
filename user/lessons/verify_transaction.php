<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../includes/db_connect.php';

// Step 1: Get transaction reference
$ref = $_GET['tx_ref'] ?? '';
if (!$ref) {
    die("❌ Transaction reference is missing.");
}

// Step 2: Fetch payment info
$stmt = $conn->prepare("SELECT email, lesson_id FROM payments WHERE transaction_id = ?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("❌ Unknown transaction reference.");
}
$paymentInfo = $result->fetch_assoc();
$stmt->close();

$userEmail = $paymentInfo['email'];
$lesson_id = $paymentInfo['lesson_id'];

// Step 3: Verify with PayChangu API
$secret = "SEC-TEST-pAOEgDVN5abHFnHkf5HCUkluBK01Pzi6"; // Use live key in production
$url = "https://api.paychangu.com/verify-payment/$ref";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $secret"
    ]
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

// Optional: Log response
file_put_contents(__DIR__ . "/paychangu_debug.txt", "TX_REF: $ref\nRESPONSE:\n$response\nERROR:\n$err\n");

if ($err) {
    die("❌ cURL Error: $err");
}

$data = json_decode($response, true);

// Step 4: Handle payment success
if (isset($data['data']['status']) && $data['data']['status'] === 'success') {
    $transaction_id = $ref;
    $amount = $data['data']['amount'] ?? 0;
    $payment_date = date('Y-m-d H:i:s');
    $status = $data['data']['status'];
    $confirmed = 1;

    // Step 5: Update payment
    $stmtUpdate = $conn->prepare("UPDATE payments SET status = ?, amount = ?, payment_date = ?, confirmed = ? WHERE transaction_id = ?");
    $stmtUpdate->bind_param("sdsis", $status, $amount, $payment_date, $confirmed, $transaction_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Step 6: Enroll user
    $stmtEnrollCheck = $conn->prepare("SELECT id FROM enrollments WHERE email = ? AND lesson_id = ?");
    $stmtEnrollCheck->bind_param("si", $userEmail, $lesson_id);
    $stmtEnrollCheck->execute();
    $stmtEnrollCheck->store_result();

    if ($stmtEnrollCheck->num_rows === 0) {
        $stmtEnrollCheck->close();

        $userFullName = explode('@', $userEmail)[0];
        $paymentStatus = "Paid";
        $stmtEnroll = $conn->prepare("INSERT INTO enrollments (full_name, email, lesson_id, payment_status, enrolled_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtEnroll->bind_param("ssis", $userFullName, $userEmail, $lesson_id, $paymentStatus);
        $stmtEnroll->execute();
        $stmtEnroll->close();
    } else {
        $stmtEnrollCheck->close();
    }

    // Step 7: Redirect to success page
    header("Location: payment_success.php?tx_ref=" . urlencode($transaction_id) . "&lesson_id=" . $lesson_id);
    exit;

} else {
    // ❌ Handle failure
    $status = $data['data']['status'] ?? 'unknown';
    $msg = $data['message'] ?? 'No message returned';

    header("Location: payment_failed.php?tx_ref=" . urlencode($ref) . "&status=$status&message=" . urlencode($msg));
    exit;
}
?>
