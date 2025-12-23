<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Debug mode is ON.<br>";

// Try to include your config files to see if they fail
echo "Trying to load config/functions.php...<br>";
require_once 'config/functions.php';
echo "Loaded successfully.<br>";
?>