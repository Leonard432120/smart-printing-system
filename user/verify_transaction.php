<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'includes/db_connect.php';

// Step 1: Get transaction reference
$ref = $_GET['tx_ref'] ?? '';
if (!$ref) {
    die("❌ Transaction reference is missing.");
}

// Step 2: Fetch payment info from database
try {
    $stmt = $conn->prepare("
        SELECT p.*, t.id as document_id 
        FROM payments p
        LEFT JOIN transactions t ON p.transaction_id = t.id
        WHERE p.transaction_reference = ?
    ");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Payment not found for reference: $ref");
        throw new Exception("Unknown transaction reference: $ref");
    }
    
    $paymentInfo = $result->fetch_assoc();
    $stmt->close();
    
    $userEmail = $paymentInfo['email'];
    $transaction_id = $paymentInfo['transaction_id'];
    $document_id = $paymentInfo['document_id'] ?? null;
    $amount = $paymentInfo['amount'];

    // Step 3: Verify with PayChangu API
    $secret = "SEC-TEST-pAOEgDVN5abHFnHkf5HCUkluBK01Pzi6";
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

    // Log response for debugging
    file_put_contents(__DIR__ . "/paychangu_debug.txt", 
        "TX_REF: $ref\nRESPONSE:\n$response\nERROR:\n$err\n", 
        FILE_APPEND
    );

    if ($err) {
        throw new Exception("cURL Error: $err");
    }

    $data = json_decode($response, true);

    // Step 4: Payment Status Check
    if (isset($data['data']['status']) && $data['data']['status'] === 'success') {
        $payment_amount = $data['data']['amount'] ?? $amount;
        $payment_date = date('Y-m-d H:i:s');
        
        // Step 5: Update payment record
        $update = $conn->prepare("
            UPDATE payments 
            SET status = 'success', 
                amount = ?, 
                payment_date = ?, 
                confirmed = 1,
                paid_at = NOW()
            WHERE transaction_reference = ?
        ");
        $update->bind_param("dss", $payment_amount, $payment_date, $ref);
        $update->execute();
        $update->close();

        // Step 6: Update transaction status if document payment
        if ($document_id) {
            $updateTx = $conn->prepare("
                UPDATE transactions 
                SET status = 'Paid' 
                WHERE id = ?
            ");
            $updateTx->bind_param("i", $document_id);
            $updateTx->execute();
            $updateTx->close();
        }

        // Step 7: Redirect to success page
        header("Location: payment_success.php?tx_ref=" . urlencode($ref) . 
              "&document_id=" . $document_id);
        exit;
    } else {
        $status = $data['data']['status'] ?? 'unknown';
        $msg = $data['message'] ?? 'No message returned';
        header("Location: payment_failed.php?tx_ref=" . urlencode($ref) . 
              "&status=$status&message=" . urlencode($msg));
        exit;
    }
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    die("❌ Payment verification failed. Please contact support with reference: $ref");
}
?>