<?php
// config/esewa_config.php
// eSewa Sandbox Configuration - FOR ACADEMIC DEMO ONLY
define('ESEWA_SANDBOX_MODE', true);
define('ESEWA_MERCHANT_CODE', 'EPAYTEST'); // Sandbox merchant code[citation:4]
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'); // Sandbox secret key[citation:4][citation:7]
define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'); // Sandbox URL[citation:4]
define('ESEWA_STATUS_CHECK_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/'); // Sandbox status URL[citation:4]

// YOUR APPLICATION'S CALLBACK URLs
define('ESEWA_SUCCESS_URL', 'http://localhost/BagCom/esewa_success.php'); // Point to your new file
define('ESEWA_FAILURE_URL', 'http://localhost/BagCom/esewa_failure.php'); // Point to your new file
?>