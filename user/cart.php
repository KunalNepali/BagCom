<?php
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = sanitize($_GET['remove']);
    $conn = getConnection();
    $sql = "DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'";
    mysqli_query($conn, $sql);
    closeConnection($conn);
    redirect('cart.php');
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        if ($quantity > 0) {
            $conn = getConnection();
            $cart_id = mysqli_real_escape_string($conn, $cart_id);
            $quantity = mysqli_real_escape_string($conn, $quantity);
            $sql = "UPDATE cart SET quantity = '$quantity' WHERE id = '$cart_id' AND user_id = '$user_id'";
            mysqli_query($conn, $sql);
            closeConnection($conn);
        }
    }
    redirect('cart.php');
}

// Get cart items
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <h2>Your Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="../products.php">Continue shopping</a>
        </div>
        <?php else: ?>
        
        <form method="POST" action="">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/products/<?php echo $item['image']; ?>" 
                                         alt="<?php echo $item['name']; ?>" 
                                         width="60" class="me-3">
                                    <div>
                                        <h6><?php echo $item['name']; ?></h6>
                                    </div>
                                </div>
                            </td>
                            <td>$<?php echo $item['price']; ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                       class="form-control" style="width: 80px;">
                            </td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <a href="?remove=<?php echo $item['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Remove this item?')">
                                    Remove
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mb-3">
                <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                <a href="../products.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Order Summary</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-end">$<?php echo number_format($cart_total, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Shipping</td>
                        <td class="text-end">$5.00</td>
                    </tr>
                    <tr class="table-active">
                        <th>Total</th>
                        <th class="text-end">$<?php echo number_format($cart_total + 5, 2); ?></th>
                    </tr>
                </table>
                
                <?php if (!empty($cart_items)): ?>
                <div class="d-grid">
                    <a href="checkout.php" class="btn btn-success btn-lg">Proceed to Checkout</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>