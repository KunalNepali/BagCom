<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'database.php';

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get user by ID
function getUserById($id) {
    $conn = getConnection();
    $id = mysqli_real_escape_string($conn, $id);
    
    $sql = "SELECT * FROM users WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    closeConnection($conn);
    return null;
}

// Get all products
function getAllProducts($limit = null) {
    $conn = getConnection();
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $result = mysqli_query($conn, $sql);
    $products = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    closeConnection($conn);
    return $products;
}

// Get product by ID
function getProductById($id) {
    $conn = getConnection();
    $id = mysqli_real_escape_string($conn, $id);
    
    $sql = "SELECT * FROM products WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        closeConnection($conn);
        return $product;
    }
    
    closeConnection($conn);
    return null;
}

// Add to cart
// In config/functions.php - add this function
function addToCart($user_id, $product_id, $quantity = 1) {
    $conn = getConnection();
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $product_id = mysqli_real_escape_string($conn, $product_id);
    $quantity = mysqli_real_escape_string($conn, $quantity);
    
    // Check if already in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        // Update quantity
        $sql = "UPDATE cart SET quantity = quantity + $quantity 
                WHERE user_id = '$user_id' AND product_id = '$product_id'";
    } else {
        // Insert new
        $sql = "INSERT INTO cart (user_id, product_id, quantity) 
                VALUES ('$user_id', '$product_id', '$quantity')";
    }
    
    $success = mysqli_query($conn, $sql);
    closeConnection($conn);
    return $success;
}
// Get cart items for user
function getCartItems($user_id) {
    $conn = getConnection();
    
    $sql = "SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = '$user_id'";
    
    $result = mysqli_query($conn, $sql);
    $items = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    closeConnection($conn);
    return $items;
}

// Calculate cart total
function getCartTotal($user_id) {
    $items = getCartItems($user_id);
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

?>