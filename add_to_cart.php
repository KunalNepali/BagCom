<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['HTTP_REFERER'] ?? 'products.php';
    redirect('login.php');
}

if (isset($_GET['id'])) {
    $product_id = sanitize($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if product exists
    $product = getProductById($product_id);
    if (!$product) {
        die("Product not found!");
    }
    
    // Add to cart
    if (addToCart($user_id, $product_id)) {
        $_SESSION['success_message'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add product to cart.";
    }
    
    // Redirect back to previous page
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'products.php';
    redirect($redirect_url);
} else {
    redirect('products.php');
}
?>