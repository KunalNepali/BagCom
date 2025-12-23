<?php
require_once 'config/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = getCartTotal($user_id);

if (empty($cart_items)) {
    redirect('cart.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    if ($payment_method === 'eSewa Demo') {
        // Create order first
        $order_number = 'ORD' . date('YmdHis') . $user_id;
        $total_amount = $cart_total + 5; // Adding shipping
        
        $conn = getConnection();
        
        // Create order in database
        $sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, payment_method, status, customer_name, customer_email, customer_phone) 
                VALUES ('$user_id', '$order_number', '$total_amount', '$shipping_address', 'eSewa Demo', 'pending', '$full_name', '$email', '$phone')";
        
        if (mysqli_query($conn, $sql)) {
            $order_id = mysqli_insert_id($conn);
            
            // Save order items
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES ('$order_id', '$product_id', '$quantity', '$price')";
                mysqli_query($conn, $sql);
            }
            
            // Create transaction record
            $transaction_uuid = 'TXN-' . time() . '-' . $order_id;
            $sql = "INSERT INTO esewa_transactions (order_id, transaction_uuid, total_amount, status) 
                    VALUES ('$order_id', '$transaction_uuid', '$total_amount', 'INITIATED')";
            mysqli_query($conn, $sql);
            
            // Clear cart
            $sql = "DELETE FROM cart WHERE user_id = '$user_id'";
            mysqli_query($conn, $sql);
            
            closeConnection($conn);
            
            // Redirect to eSewa payment page
            redirect("esewa_payment.php?order_id=$order_id");
        } else {
            $error = "Error creating order: " . mysqli_error($conn);
            closeConnection($conn);
        }
    } 
    // ADDED: Cash on Delivery payment processing
    else 
if ($payment_method === 'COD') {
    // Create order for COD
    $order_number = 'COD-' . date('YmdHis') . $user_id;
    $total_amount = $cart_total + 5; // Adding shipping
    
    $conn = getConnection();
    
    // FIXED: Changed 'confirmed' to 'processing' to match ENUM
    $sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, payment_method, status, customer_name, customer_email, customer_phone) 
            VALUES ('$user_id', '$order_number', '$total_amount', '$shipping_address', 'Cash on Delivery', 'processing', '$full_name', '$email', '$phone')";
        
        if (mysqli_query($conn, $sql)) {
            $order_id = mysqli_insert_id($conn);
            
            // Save order items
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES ('$order_id', '$product_id', '$quantity', '$price')";
                mysqli_query($conn, $sql);
                
                // Update product stock
                $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity 
                        WHERE id = '$product_id'";
                mysqli_query($conn, $sql);
            }
            
            // Clear cart
            $sql = "DELETE FROM cart WHERE user_id = '$user_id'";
            mysqli_query($conn, $sql);
            
            closeConnection($conn);
            
            // Store order ID in session for confirmation page
            $_SESSION['last_order_id'] = $order_id;
            $_SESSION['payment_method'] = 'Cash on Delivery';
            
            // Redirect to order confirmation page
            redirect("order_confirmation.php");
        } else {
            $error = "Error creating COD order: " . mysqli_error($conn);
            closeConnection($conn);
        }
    } else {
        $error = "Please select a valid payment method.";
    }
}

// Get user info
$user = getUserById($user_id);
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">Checkout</h2>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo $user['full_name'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $user['email'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo $user['phone'] ?? ''; ?>" required 
                                   pattern="[0-9]{10}" title="Enter 10-digit phone number">
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php 
                                echo $user['address'] ?? '';
                            ?></textarea>
                            <small class="text-muted">Please include street address, city, and postal code</small>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="esewa_demo" value="eSewa Demo" checked>
                            <label class="form-check-label" for="esewa_demo">
                                <strong>eSewa Sandbox Payment</strong> 
                                <span class="badge bg-warning">Demo Mode</span>
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-info-circle"></i> Use test credentials: 
                                    ID: 9806800001 | Password: Nepal@123 | OTP: 123456
                                </small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD">
                            <label class="form-check-label" for="cod">
                                <strong>Cash on Delivery (COD)</strong>
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-info-circle"></i> Pay with cash when your order is delivered
                                </small>
                            </label>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-flask"></i> <strong>Academic Project Note:</strong> 
                            This is a demonstration system. No real payments will be processed.
                        </div>
                        
                        <!-- Payment Method Instructions -->
                        <div id="codInstructions" class="alert alert-warning d-none">
                            <h6><i class="fas fa-money-bill-wave"></i> Cash on Delivery Details</h6>
                            <ul class="mb-0">
                                <li>Pay with cash when your order arrives</li>
                                <li>Delivery within 3-5 business days</li>
                                <li>Have exact amount ready for the delivery person</li>
                                <li>Order confirmation will be shown immediately</li>
                            </ul>
                        </div>
                        
                        <div id="esewaInstructions" class="alert alert-primary">
                            <h6><i class="fas fa-credit-card"></i> eSewa Sandbox Instructions</h6>
                            <ul class="mb-0">
                                <li>You'll be redirected to eSewa's test environment</li>
                                <li>Use test credentials provided above</li>
                                <li>Complete the payment simulation</li>
                                <li>You'll return to this site for confirmation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Review</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="60">Image</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): 
                                        $image_path = 'uploads/products/' . $item['image'];
                                        $image_url = file_exists($image_path) ? $image_path : 'uploads/products/default.jpg';
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $image_url; ?>" 
                                                 alt="<?php echo $item['name']; ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div><?php echo $item['name']; ?></div>
                                            <small class="text-muted">SKU: <?php echo $item['product_id']; ?></small>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                        <td><strong>$<?php echo number_format($cart_total, 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Shipping Fee</td>
                                        <td>$5.00</td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end"><strong>Total Amount</strong></td>
                                        <td><strong>$<?php echo number_format($cart_total + 5, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-lock"></i> Place Order & Proceed to Payment
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success"></i>
                                Secure checkout • 30-day return policy • 24/7 support
                            </small>
                            <br>
                            <small class="text-muted">
                                By placing your order, you agree to our 
                                <a href="#">Terms & Conditions</a> and 
                                <a href="#">Privacy Policy</a>
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Order Items (<?php echo count($cart_items); ?>)</h6>
                        <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <small>
                                <img src="uploads/products/<?php echo $item['image']; ?>" 
                                     alt="<?php echo $item['name']; ?>" 
                                     width="30" height="30" 
                                     style="object-fit: cover; border-radius: 3px; margin-right: 5px;">
                                <?php echo $item['name']; ?> × <?php echo $item['quantity']; ?>
                            </small>
                            <small>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>$5.00</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total Amount</strong>
                        <strong class="text-primary">$<?php echo number_format($cart_total + 5, 2); ?></strong>
                    </div>
                    
                    <!-- Dynamic payment method info -->
                    <div class="alert alert-info small">
                        <div id="currentPaymentInfo">
                            <i class="fas fa-credit-card"></i>
                            <strong>Selected:</strong> eSewa Sandbox Payment
                        </div>
                    </div>
                    
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> This is a demo checkout for academic purposes.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide payment method instructions
document.addEventListener('DOMContentLoaded', function() {
    const codRadio = document.getElementById('cod');
    const esewaRadio = document.getElementById('esewa_demo');
    const codInstructions = document.getElementById('codInstructions');
    const esewaInstructions = document.getElementById('esewaInstructions');
    const currentPaymentInfo = document.getElementById('currentPaymentInfo');
    
    function updatePaymentDisplay() {
        if (codRadio.checked) {
            codInstructions.classList.remove('d-none');
            esewaInstructions.classList.add('d-none');
            currentPaymentInfo.innerHTML = `
                <i class="fas fa-money-bill-wave"></i>
                <strong>Selected:</strong> Cash on Delivery (COD)
            `;
        } else {
            codInstructions.classList.add('d-none');
            esewaInstructions.classList.remove('d-none');
            currentPaymentInfo.innerHTML = `
                <i class="fas fa-credit-card"></i>
                <strong>Selected:</strong> eSewa Sandbox Payment
            `;
        }
    }
    
    // Add event listeners
    codRadio.addEventListener('change', updatePaymentDisplay);
    esewaRadio.addEventListener('change', updatePaymentDisplay);
    
    // Initial update
    updatePaymentDisplay();
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const phone = document.getElementById('phone').value;
        const phoneRegex = /^[0-9]{10}$/;
        
        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            alert('Please enter a valid 10-digit phone number');
            document.getElementById('phone').focus();
        }
        
        // Confirm COD selection
        if (codRadio.checked) {
            if (!confirm('You selected Cash on Delivery. Continue to place order?')) {
                e.preventDefault();
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>