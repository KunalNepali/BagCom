<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Admin Setup:<br><br>";

// Test 1: Check files
$files = [
    '../config/admin_auth.php',
    '../config/database.php',
    'includes/admin_header.php'
];

foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "✅ Found" : "❌ Missing") . "<br>";
}

// Test 2: Check database connection
echo "<br>Database connection: ";
try {
    require_once '../config/database.php';
    $conn = getConnection();
    echo "✅ Connected";
    closeConnection($conn);
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage();
}

// Test 3: Check session
echo "<br>Session status: " . session_status();
?>