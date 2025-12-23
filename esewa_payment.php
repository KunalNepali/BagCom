<?php
require_once 'config/functions.php';
require_once 'config/esewa_config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : 0;

if (!$order_id) {
    die("Invalid order ID.");
}

// Get order details
$conn = getConnection();
$sql = "SELECT * FROM orders WHERE id = '$order_id' AND user_id = '{$_SESSION['user_id']}'";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Order not found.");
}

$order = mysqli_fetch_assoc($result);
$total_amount = $order['total_amount'];

// Generate transaction data for eSewa
$transaction_uuid = 'TXN-' . time() . '-' . $order_id;

$data = [
    'amount' => ($total_amount - 5), // Subtract shipping for product amount
    'tax_amount' => '0',
    'total_amount' => $total_amount,
    'transaction_uuid' => $transaction_uuid,
    'product_code' => ESEWA_MERCHANT_CODE,
    'product_service_charge' => '0',
    'product_delivery_charge' => '5',
    'success_url' => ESEWA_SUCCESS_URL,
    'failure_url' => ESEWA_FAILURE_URL,
    'signed_field_names' => 'total_amount,transaction_uuid,product_code'
];

// Generate signature
$message = "total_amount={$data['total_amount']},transaction_uuid={$data['transaction_uuid']},product_code={$data['product_code']}";
$signature = hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true);
$data['signature'] = base64_encode($signature);

// Update transaction status
$sql = "UPDATE esewa_transactions 
        SET status = 'PENDING', transaction_date = NOW() 
        WHERE order_id = '$order_id' AND transaction_uuid = '$transaction_uuid'";
mysqli_query($conn, $sql);

closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa - BagCom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .demo-banner {
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
            margin: 30px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="demo-banner">
            🧪 SANDBOX / DEMO PAYMENT INTEGRATION - FOR ACADEMIC PURPOSES ONLY
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-credit-card"></i> Processing Payment</h4>
            </div>
            <div class="card-body text-center">
                <div class="loader"></div>
                <h3>Redirecting to eSewa Sandbox...</h3>
                <p class="text-muted">Please wait while we redirect you to eSewa's secure payment gateway.</p>
                
                <div class="alert alert-warning mt-4">
                    <h5><i class="fas fa-user-check"></i> Test Credentials</h5>
                    <p class="mb-1"><strong>eSewa ID:</strong> 9806800001</p>
                    <p class="mb-1"><strong>Password:</strong> Nepal@123</p>
                    <p><strong>OTP:</strong> 123456</p>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    You will be redirected to eSewa's test environment for payment simulation.
                    No real money will be transferred.
                </div>
                
                <!-- Hidden form that auto-submits to eSewa -->
                <form id="esewaPaymentForm" method="POST" action="<?php echo ESEWA_PAYMENT_URL; ?>">
                    <?php foreach ($data as $key => $value): ?>
                        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endforeach; ?>
                </form>
                
                <div class="mt-4">
                    <p class="text-muted">If you are not redirected automatically in 5 seconds:</p>
                    <button onclick="submitForm()" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Click Here to Continue
                    </button>
                    <a href="checkout.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-times"></i> Cancel Payment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form after 3 seconds
        setTimeout(function() {
            document.getElementById('esewaPaymentForm').submit();
        }, 3000);
        
        // Manual submit function
        function submitForm() {
            document.getElementById('esewaPaymentForm').submit();
        }
    </script>
</body>
</html>