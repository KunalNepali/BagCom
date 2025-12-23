<?php
echo "<h3>Testing File Paths</h3>";

$paths = [
    'admin_header.php' => __FILE__,
    '../config/admin_auth.php' => realpath(__DIR__ . '/../config/admin_auth.php'),
    '../config/database.php' => realpath(__DIR__ . '/../config/database.php'),
    '../../config/admin_auth.php' => realpath(__DIR__ . '/../../config/admin_auth.php'),
];

foreach ($paths as $path => $realpath) {
    echo "<strong>$path:</strong> " . 
         ($realpath ? "✅ " . $realpath : "❌ NOT FOUND") . "<br>";
}
?>