<?php
// Admin Authentication Functions

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// Get current admin info
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    require_once 'database.php';
    $conn = getConnection();
    $admin_id = mysqli_real_escape_string($conn, $_SESSION['admin_id']);
    
    $sql = "SELECT * FROM admins WHERE id = '$admin_id' AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    closeConnection($conn);
    return null;
}

// Log admin action
function logAdminAction($action, $details = '') {
    if (!isAdminLoggedIn()) return;
    
    require_once 'database.php';
    $conn = getConnection();
    
    $admin_id = mysqli_real_escape_string($conn, $_SESSION['admin_id']);
    $action = mysqli_real_escape_string($conn, $action);
    $details = mysqli_real_escape_string($conn, $details);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) 
            VALUES ('$admin_id', '$action', '$details', '$ip_address', '$user_agent')";
    
    mysqli_query($conn, $sql);
    closeConnection($conn);
}

// Validate admin permissions
function hasAdminPermission($permission) {
    $admin = getCurrentAdmin();
    if (!$admin) return false;
    
    // Super admin has all permissions
    if ($admin['admin_type'] === 'super_admin') {
        return true;
    }
    
    // Add permission logic here if needed
    return true;
}
?>