<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/functions.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('products.php');
}

$product_id = sanitize($_GET['id']);
$product = getProductById($product_id);

// If product not found
if (!$product) {
    $_SESSION['error_message'] = "Product not found!";
    redirect('products.php');
}

// Generate image path
$image_path = 'uploads/products/' . $product['image'];
$image_exists = file_exists($image_path);
$image_url = $image_exists ? $image_path : 'uploads/products/default.jpg';

// Handle add to cart if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = "product_detail.php?id=$product_id";
        redirect('login.php');
    }
    
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = $_SESSION['user_id'];
    
    // Validate quantity
    if ($quantity < 1 || $quantity > $product['stock_quantity']) {
        $_SESSION['error_message'] = "Invalid quantity selected.";
    } else {
        if (addToCart($user_id, $product_id, $quantity)) {
            $_SESSION['success_message'] = "Added $quantity item(s) to cart!";
            redirect('cart.php');
        } else {
            $_SESSION['error_message'] = "Failed to add to cart. Please try again.";
        }
    }
}

// Get related products (same category)
$conn = getConnection();
$category = mysqli_real_escape_string($conn, $product['category']);
$sql = "SELECT * FROM products 
        WHERE category = '$category' AND id != '$product_id' 
        ORDER BY RAND() LIMIT 4";
$related_result = mysqli_query($conn, $sql);
closeConnection($conn);

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo urlencode($product['category']); ?>">
                <?php echo htmlspecialchars($product['category']); ?>
            </a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Image Gallery -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <!-- Main Product Image -->
                    <div class="text-center mb-3">
                        <img id="mainProductImage" 
                             src="<?php echo $image_url; ?>" 
                             class="img-fluid rounded" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="max-height: 400px; width: auto; object-fit: contain;">
                    </div>
                    
                    <!-- Image Zoom Indicator -->
                    <div class="text-center mb-3">
                        <small class="text-muted">
                            <i class="fas fa-search-plus"></i> Click and drag to zoom on desktop
                        </small>
                    </div>
                    
                    <!-- Thumbnail Images -->
                    <div class="row g-2" id="thumbnailGallery">
                        <!-- Main image thumbnail -->
                        <div class="col-3">
                            <a href="javascript:void(0);" class="thumbnail-link active" 
                               data-image="<?php echo $image_url; ?>">
                                <img src="<?php echo $image_url; ?>" 
                                     class="img-thumbnail thumbnail-img" 
                                     style="height: 80px; object-fit: cover;">
                            </a>
                        </div>
                        
                        <!-- Additional thumbnails (if you have more images) -->
                        <!-- You can add more thumbnails here -->
                        <?php
                        // Example for additional images - you can extend this
                        $additional_images = ['default.jpg']; // Add your other image names
                        foreach ($additional_images as $add_img):
                            $add_img_path = 'uploads/products/' . $add_img;
                            if (file_exists($add_img_path) && $add_img != $product['image']):
                        ?>
                        <div class="col-3">
                            <a href="javascript:void(0);" class="thumbnail-link" 
                               data-image="<?php echo $add_img_path; ?>">
                                <img src="<?php echo $add_img_path; ?>" 
                                     class="img-thumbnail thumbnail-img" 
                                     style="height: 80px; object-fit: cover;">
                            </a>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <!-- Product Title and Category -->
                    <div class="mb-3">
                        <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($product['category']); ?></span>
                        <h1 class="h2 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="d-flex align-items-center">
                            <div class="text-warning me-2">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted">(4.5/5.0) • 128 reviews</small>
                        </div>
                    </div>
                    
                    <!-- Price and Stock Status -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="h2 text-primary fw-bold me-3">
                                $<?php echo number_format($product['price'], 2); ?>
                            </span>
                            <?php if ($product['price'] > 50): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-tag"></i> Free Shipping
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?> me-2">
                                <i class="fas fa-box"></i> 
                                <?php echo $product['stock_quantity'] > 0 ? 
                                      $product['stock_quantity'] . ' in stock' : 'Out of stock'; ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-shipping-fast"></i> 
                                Usually ships in 2-3 business days
                            </small>
                        </div>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="mb-4">
                        <h5 class="mb-3">Description</h5>
                        <p class="text-muted" style="line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                    </div>
                    
                    <!-- Product Specifications -->
                    <div class="mb-4">
                        <h5 class="mb-3">Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong><i class="fas fa-box me-2"></i>SKU:</strong> 
                                        BAG-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong><i class="fas fa-tag me-2"></i>Category:</strong> 
                                        <?php echo htmlspecialchars($product['category']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong><i class="fas fa-calendar me-2"></i>Added:</strong> 
                                        <?php echo date('F j, Y', strtotime($product['created_at'])); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong><i class="fas fa-weight me-2"></i>Weight:</strong> Approx. 1.2 kg
                                    </li>
                                    <li class="mb-2">
                                        <strong><i class="fas fa-ruler-combined me-2"></i>Dimensions:</strong> 30 × 45 × 15 cm
                                    </li>
                                    <li class="mb-2">
                                        <strong><i class="fas fa-palette me-2"></i>Colors:</strong> Black, Blue, Gray
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Form -->
                    <form method="POST" action="">
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">Purchase Options</h5>
                                
                                <!-- Quantity Selector -->
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-4">
                                        <label for="quantity" class="form-label fw-bold">Quantity:</label>
                                        <div class="input-group" style="width: 120px;">
                                            <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(-1)">-</button>
                                            <input type="number" 
                                                   id="quantity" 
                                                   name="quantity" 
                                                   value="1" 
                                                   min="1" 
                                                   max="<?php echo $product['stock_quantity']; ?>"
                                                   class="form-control text-center"
                                                   <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                            <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(1)">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-info-circle text-primary"></i>
                                            Maximum <?php echo $product['stock_quantity']; ?> units per order
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2 d-md-flex">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <?php if (isLoggedIn()): ?>
                                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg flex-fill me-md-2">
                                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-lg flex-fill" 
                                                    onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                                <i class="far fa-heart me-2"></i> Wishlist
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php?redirect=product_detail.php?id=<?php echo $product_id; ?>" 
                                               class="btn btn-primary btn-lg flex-fill me-md-2">
                                                <i class="fas fa-sign-in-alt me-2"></i> Login to Purchase
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg flex-fill" disabled>
                                            <i class="fas fa-times-circle me-2"></i> Out of Stock
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-lg flex-fill"
                                                onclick="notifyWhenAvailable(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-bell me-2"></i> Notify Me
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Secure Checkout Note -->
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-lock text-success"></i>
                                        Secure checkout • 30-day return policy • 1-year warranty
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Share and Compare -->
                    <div class="d-flex justify-content-between border-top pt-3">
                        <div>
                            <span class="text-muted me-3">Share:</span>
                            <a href="#" class="text-muted me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-muted me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-muted me-2"><i class="fab fa-pinterest"></i></a>
                            <a href="#" class="text-muted"><i class="fas fa-envelope"></i></a>
                        </div>
                        <div>
                            <a href="#" class="text-muted text-decoration-none">
                                <i class="fas fa-balance-scale me-1"></i> Compare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (mysqli_num_rows($related_result) > 0): ?>
    <div class="mt-5">
        <h3 class="mb-4">You Might Also Like</h3>
        <div class="row">
            <?php while ($related_product = mysqli_fetch_assoc($related_result)): 
                $related_image = 'uploads/products/' . $related_product['image'];
                $related_image_url = file_exists($related_image) ? $related_image : 'uploads/products/default.jpg';
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <a href="product_detail.php?id=<?php echo $related_product['id']; ?>">
                        <img src="<?php echo $related_image_url; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($related_product['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                    </a>
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="product_detail.php?id=<?php echo $related_product['id']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($related_product['name']); ?>
                            </a>
                        </h6>
                        <p class="card-text text-primary fw-bold mb-2">
                            $<?php echo number_format($related_product['price'], 2); ?>
                        </p>
                        <a href="product_detail.php?id=<?php echo $related_product['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">View Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Product Detail Page -->
<script>
// Thumbnail Gallery Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Thumbnail click handler
    const thumbnailLinks = document.querySelectorAll('.thumbnail-link');
    const mainImage = document.getElementById('mainProductImage');
    
    thumbnailLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all thumbnails
            thumbnailLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Update main image
            const newImageSrc = this.getAttribute('data-image');
            mainImage.src = newImageSrc;
        });
    });
    
    // Image zoom on hover (desktop)
    mainImage.addEventListener('mouseenter', function() {
        this.style.cursor = 'zoom-in';
    });
    
    // Simple image zoom effect
    mainImage.addEventListener('click', function() {
        if (this.style.transform === 'scale(1.5)') {
            this.style.transform = 'scale(1)';
            this.style.cursor = 'zoom-in';
        } else {
            this.style.transform = 'scale(1.5)';
            this.style.cursor = 'zoom-out';
        }
    });
});

// Quantity update functions
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    const maxValue = parseInt(quantityInput.max);
    const minValue = parseInt(quantityInput.min);
    
    let newValue = currentValue + change;
    
    if (newValue >= minValue && newValue <= maxValue) {
        quantityInput.value = newValue;
    }
}

// Add to wishlist function
function addToWishlist(productId) {
    // For demo - you can implement AJAX call here
    alert('Product added to wishlist! (Demo function)');
}

// Notify when available function
function notifyWhenAvailable(productId) {
    const email = prompt('Enter your email to be notified when this product is back in stock:');
    if (email) {
        alert('Thank you! We will notify you at ' + email + ' when this product is available.');
        // You can add AJAX call here to save email
    }
}
</script>

<?php include 'includes/footer.php'; ?>