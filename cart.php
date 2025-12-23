<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle cart actions
if (isset($_GET['remove'])) {
    $cart_id = sanitize($_GET['remove']);
    $conn = getConnection();
    $sql = "DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'";
    mysqli_query($conn, $sql);
    closeConnection($conn);
    redirect('cart.php');
}

// Update quantities
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

$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);

include 'includes/header.php';
?>

<div class="container">
    <h2 class="my-4">Your Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
    <div class="alert alert-info">
        <i class="fas fa-shopping-cart"></i> Your cart is empty. 
        <a href="products.php" class="alert-link">Continue shopping</a>
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
                                <img src="uploads/products/<?php echo $item['image']; ?>" 
                                     alt="<?php echo $item['name']; ?>" 
                                     width="60" class="me-3 rounded">
                                <div>
                                    <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                                    <small class="text-muted">Stock: <?php echo $item['stock_quantity']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">$<?php echo $item['price']; ?></td>
                        <td class="align-middle">
                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                   class="form-control" style="width: 80px;">
                        </td>
                        <td class="align-middle">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td class="align-middle">
                            <a href="?remove=<?php echo $item['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Remove this item from cart?')">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mb-4">
            <button type="submit" name="update_cart" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Update Cart
            </button>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    </form>
    
    <div class="row">
        <div class="col-md-6 offset-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Order Summary</h5>
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
                    
                    <div class="d-grid">
                        <a href="checkout.php" class="btn btn-success btn-lg">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>