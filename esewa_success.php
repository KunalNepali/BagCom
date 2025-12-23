<?php
require_once 'config/functions.php';
require_once 'config/esewa_config.php'; // This should define your sandbox constants

// 1. GET THE RESPONSE FROM ESEWA
// eSewa sends data as a base64 encoded string in the URL[citation:4]
$encoded_response = $_GET['data'] ?? '';
if (empty($encoded_response)) {
    die("Invalid response from eSewa.");
}

// Decode the response to a PHP array[citation:4]
$decoded_data = base64_decode($encoded_response);
$response_data = json_decode($decoded_data, true);

// 2. VERIFY THE SIGNATURE (CRITICAL FOR SECURITY)[citation:4]
// Recreate the message string using the response parameters
$message = "transaction_code={$response_data['transaction_code']},status={$response_data['status']},total_amount={$response_data['total_amount']},transaction_uuid={$response_data['transaction_uuid']},product_code={$response_data['product_code']}";
// Generate the expected signature using your secret key[citation:4]
$expected_signature = hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true);
$expected_signature_base64 = base64_encode($expected_signature);

// Compare the expected signature with the one eSewa sent[citation:4]
if (!hash_equals($expected_signature_base64, $response_data['signature'])) {
    die("Payment verification failed: Invalid signature.");
}

// 3. CHECK PAYMENT STATUS[citation:4]
if ($response_data['status'] !== 'COMPLETE') {
    // Redirect to failure page if payment wasn't complete
    header("Location: esewa_failure.php");
    exit();
}

// 4. UPDATE YOUR DATABASE
$conn = getConnection();
$transaction_uuid = mysqli_real_escape_string($conn, $response_data['transaction_uuid']);
$ref_id = mysqli_real_escape_string($conn, $response_data['transaction_code']);
$total_amount = mysqli_real_escape_string($conn, $response_data['total_amount']);

// a) Update the esewa_transactions table
$update_sql = "UPDATE esewa_transactions 
               SET ref_id = '$ref_id', 
                   status = 'COMPLETE',
                   response_data = '" . json_encode($response_data) . "',
                   transaction_date = NOW()
               WHERE transaction_uuid = '$transaction_uuid'";
mysqli_query($conn, $update_sql);

// b) Get the associated order_id and update the main orders table
$fetch_sql = "SELECT order_id FROM esewa_transactions WHERE transaction_uuid = '$transaction_uuid'";
$result = mysqli_query($conn, $fetch_sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $order_id = $row['order_id'];
    $order_sql = "UPDATE orders SET status = 'processing' WHERE id = '$order_id'";
    mysqli_query($conn, $order_sql);
}
closeConnection($conn);

// 5. DISPLAY THE SUCCESS PAGE TO THE USER
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="alert alert-success text-center">
        <h2><i class="fas fa-check-circle"></i> Payment Successful!</h2>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-flask"></i> <strong>Demo Transaction:</strong> This was a sandbox test. No real money was transferred.
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Order & Transaction Confirmation</h5>
            </div>
            <div class="card-body">
                <p><strong>Reference ID:</strong> <?php echo $ref_id; ?></p>
                <p><strong>Your Order ID:</strong> <?php echo $order_id ?? 'N/A'; ?></p>
                <p><strong>Amount Paid:</strong> NPR <?php echo number_format($total_amount, 2); ?></p>
                <p><strong>Status:</strong> <span class="badge bg-success"><?php echo $response_data['status']; ?></span></p>
                <p><strong>Transaction Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                <hr>
                <p>Thank you for your order! A confirmation receipt has been recorded in the system.</p>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="user/order_confirmation.php?order_id=<?php echo $order_id ?? ''; ?>" class="btn btn-primary">
                <i class="fas fa-receipt"></i> View Detailed Order Confirmation
            </a>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-home"></i> Return to Home
            </a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>