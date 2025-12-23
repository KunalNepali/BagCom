<?php
// admin/db_connection.php
session_start();

// Include database configuration
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Admin functions
function getAdminName() {
    return isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
}
?>