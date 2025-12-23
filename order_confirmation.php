<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if order was placed
if (!isset($_SESSION['last_order_id'])) {
    redirect('products.php');
}

$order_id = $_SESSION['last_order_id'];
$payment_method = $_SESSION['payment_method'] ?? 'Unknown';

// Get order details
$conn = getConnection();
$sql = "SELECT o.*, 
               (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as subtotal
        FROM orders o 
        WHERE o.id = '$order_id' AND o.user_id = '{$_SESSION['user_id']}'";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    mysqli_close($conn);
    redirect('products.php');
}

$order = mysqli_fetch_assoc($result);

// Get order items
$sql = "SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = '$order_id'";
$items_result = mysqli_query($conn, $sql);
$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}

mysqli_close($conn);

// Clear session after displaying
unset($_SESSION['last_order_id']);
unset($_SESSION['payment_method']);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Header -->
            <div class="text-center mb-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="mb-3">Order Confirmed! 🎉</h1>
                <p class="lead text-muted">Thank you for your purchase. Your order has been received.</p>
            </div>
            
            <!-- Order Summary Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Order Information</h6>
                            <p class="mb-1"><strong>Order ID:</strong> <?php echo $order['order_number']; ?></p>
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> 
                                <span class="badge bg-info"><?php echo $payment_method; ?></span>
                            </p>
                            <p class="mb-0"><strong>Status:</strong> 
                                <span class="badge bg-success">Confirmed</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></p>
                            <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Shipping Address -->
                    <div class="mb-4">
                        <h6 class="text-muted">Shipping Address</h6>
                        <div class="border rounded p-3 bg-light">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i> Order Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Image</th>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach ($order_items as $item): 
                                    $item_total = $item['quantity'] * $item['price'];
                                    $subtotal += $item_total;
                                    $image_path = 'uploads/products/' . ($item['image'] ?? 'default.jpg');
                                ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo file_exists($image_path) ? $image_path : 'uploads/products/default.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="img-thumbnail" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">SKU: PROD-<?php echo str_pad($item['product_id'], 4, '0', STR_PAD_LEFT); ?></small>
                                    </td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Shipping Fee</td>
                                    <td class="text-end">$5.00</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Amount</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Payment Instructions for COD -->
            <?php if ($payment_method === 'Cash on Delivery'): ?>
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery Instructions</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                        <ul class="mb-0">
                            <li>Please have exact cash ready when the delivery arrives</li>
                            <li>Our delivery executive will contact you before delivery</li>
                            <li>You can pay with cash only - no cards or digital payments</li>
                            <li>Order will be delivered within 3-5 business days</li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-clock text-primary me-2"></i>Delivery Timeline</h6>
                                    <p class="mb-2">1. Order Confirmation (Instant)</p>
                                    <p class="mb-2">2. Processing (1-2 days)</p>
                                    <p class="mb-2">3. Shipping (1-2 days)</p>
                                    <p class="mb-0">4. Delivery (1 day)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-phone text-primary me-2"></i>Need Help?</h6>
                                    <p class="mb-1">Customer Support: +1 (555) 123-4567</p>
                                    <p class="mb-0">Email: support@bagcom.com</p>
                                    <p class="mb-0">Hours: Mon-Fri, 9AM-6PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="text-center">
                <div class="d-grid gap-2 d-md-flex justify-content-center">
                    <a href="index.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-home me-2"></i> Continue Shopping
                    </a>
                    <a href="my_orders.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-clipboard-list me-2"></i> View All Orders
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </button>
                </div>
                
                <div class="mt-4">
                    <a href="products.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <!-- Order Tracking Info -->
            <div class="mt-5 text-center">
                <h6 class="text-muted mb-3">Track Your Order</h6>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" 
                         aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div>
                        <i class="fas fa-check-circle text-success"></i>
                        <div class="small">Order Placed</div>
                    </div>
                    <div>
                        <i class="fas fa-box text-muted"></i>
                        <div class="small">Processing</div>
                    </div>
                    <div>
                        <i class="fas fa-shipping-fast text-muted"></i>
                        <div class="small">Shipped</div>
                    </div>
                    <div>
                        <i class="fas fa-home text-muted"></i>
                        <div class="small">Delivered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-print option (optional)
// setTimeout(function() {
//     if(confirm('Would you like to print your receipt?')) {
//         window.print();
//     }
// }, 3000);

// Countdown timer for order processing
let countdown = 5;
const timerElement = document.createElement('div');
timerElement.className = 'alert alert-info text-center mt-3';
document.querySelector('.text-center').appendChild(timerElement);

setInterval(function() {
    timerElement.innerHTML = `<i class="fas fa-clock me-2"></i>Redirecting to homepage in ${countdown} seconds...`;
    countdown--;
    
    if (countdown < 0) {
        window.location.href = 'index.php';
    }
}, 1000);
</script>

<?php include 'includes/footer.php'; ?>