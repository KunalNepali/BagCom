<?php
require_once 'config/functions.php';
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="alert alert-danger text-center">
        <h2><i class="fas fa-times-circle"></i> Payment Not Completed</h2>
        <div class="alert alert-info mt-3">
            <i class="fas fa-flask"></i> <strong>Demo Note:</strong> This was a test in the sandbox environment.
        </div>
        <p class="mt-3">The payment process was not completed. This could be because:</p>
        <ul class="text-start" style="display: inline-block;">
            <li>You cancelled the payment in the sandbox.</li>
            <li>The test session expired.</li>
            <li>There was a simulation error.</li>
        </ul>
        
        <div class="mt-4">
            <a href="cart.php" class="btn btn-warning">
                <i class="fas fa-shopping-cart"></i> Return to Cart
            </a>
            <a href="products.php" class="btn btn-outline-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>