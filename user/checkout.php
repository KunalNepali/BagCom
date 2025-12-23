<?php
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);

if (empty($cart_items)) {
    redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . str_pad($user_id, 4, '0', STR_PAD_LEFT) . rand(100, 999);
    
    $conn = getConnection();
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order
        $total_amount = $cart_total + 5; // Add shipping
        $sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, payment_method) 
                VALUES ('$user_id', '$order_number', '$total_amount', '$shipping_address', '$payment_method')";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception(mysqli_error($conn));
        }
        
        $order_id = mysqli_insert_id($conn);
        
        // Add order items
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES ('$order_id', '$product_id', '$quantity', '$price')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Update product stock
            $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity 
                    WHERE id = '$product_id'";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception(mysqli_error($conn));
            }
        }
        
        // Clear cart
        $sql = "DELETE FROM cart WHERE user_id = '$user_id'";
        mysqli_query($conn, $sql);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect to confirmation page
        redirect("order_confirmation.php?order_id=$order_id");
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error = "Order failed: " . $e->getMessage();
    }
    
    closeConnection($conn);
}

// Get user info
$user = getUserById($user_id);
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <h2>Checkout</h2>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Shipping Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="shipping_address" class="form-label">Shipping Address</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php 
                            echo $user['address'] ?? '';
                        ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Payment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                        <label class="form-check-label" for="cod">
                            Cash on Delivery (COD)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="card" value="Credit Card">
                        <label class="form-check-label" for="card">
                            Credit/Debit Card (Simulation)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="PayPal">
                        <label class="form-check-label" for="paypal">
                            PayPal (Simulation)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Order Review</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td>$<?php echo $item['price']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">Subtotal</td>
                                    <td>$<?php echo number_format($cart_total, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Shipping</td>
                                    <td>$5.00</td>
                                </tr>
                                <tr class="table-active">
                                    <th colspan="3">Total</th>
                                    <th>$<?php echo number_format($cart_total + 5, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>