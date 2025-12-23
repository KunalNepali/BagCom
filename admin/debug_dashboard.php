<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debugging Admin Dashboard</h3>";

// Test 1: Check if config file exists
$config_path = '../config/admin_auth.php';
echo "1. Checking config/admin_auth.php: ";
if (file_exists($config_path)) {
    echo "✅ Found<br>";
} else {
    echo "❌ NOT FOUND<br>";
}

// Test 2: Check if functions file exists
$functions_path = '../config/functions.php';
echo "2. Checking config/functions.php: ";
if (file_exists($functions_path)) {
    echo "✅ Found<br>";
} else {
    echo "❌ NOT FOUND<br>";
}

// Test 3: Check if database file exists
$database_path = '../config/database.php';
echo "3. Checking config/database.php: ";
if (file_exists($database_path)) {
    echo "✅ Found<br>";
} else {
    echo "❌ NOT FOUND<br>";
}

// Test 4: Try to include admin_auth
echo "4. Including admin_auth.php...<br>";
try {
    require_once $config_path;
    echo "✅ admin_auth.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Check if session is started
echo "5. Session status: " . session_status() . " (2 = PHP_SESSION_ACTIVE)<br>";

// Test 6: Check if admin is logged in
if (function_exists('isAdminLoggedIn')) {
    echo "6. isAdminLoggedIn() function: ✅ Exists<br>";
    echo "   Return value: " . (isAdminLoggedIn() ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "6. isAdminLoggedIn() function: ❌ NOT FOUND<br>";
}
?>