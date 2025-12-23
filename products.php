<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/functions.php';

// Get all products from database
$products = getAllProducts();

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Our Products</h1>
    
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
    
    <!-- Product Filter/Search (Optional) -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search products..." 
                       value="<?php echo $_GET['search'] ?? ''; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    Sort by: Price
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?sort=price_asc">Price: Low to High</a></li>
                    <li><a class="dropdown-item" href="?sort=price_desc">Price: High to Low</a></li>
                    <li><a class="dropdown-item" href="?sort=newest">Newest First</a></li>
                    <li><a class="dropdown-item" href="?sort=popular">Most Popular</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Product Count -->
    <div class="mb-4">
        <p class="text-muted">
            <i class="fas fa-box"></i> Showing <?php echo count($products); ?> product(s)
        </p>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
            <h4>No products found</h4>
            <p>Try adjusting your search or check back later for new arrivals.</p>
            <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): 
                // Generate image path
                $image_path = 'uploads/products/' . $product['image'];
                $image_exists = file_exists($image_path);
                $image_url = $image_exists ? $image_path : 'uploads/products/default.jpg';
                
                // Stock status
                $stock_class = $product['stock_quantity'] > 10 ? 'success' : 
                              ($product['stock_quantity'] > 0 ? 'warning' : 'danger');
                $stock_text = $product['stock_quantity'] > 0 ? 
                             $product['stock_quantity'] . ' in stock' : 'Out of stock';
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card h-100 product-card shadow-sm">
                    <!-- Product Image Container -->
                    <div class="position-relative" style="height: 250px; overflow: hidden;">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo $image_url; ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                        </a>
                        
                        <!-- Product Badges -->
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            <span class="badge bg-danger position-absolute" style="top: 10px; left: 10px;">
                                <i class="fas fa-times-circle"></i> Sold Out
                            </span>
                        <?php elseif ($product['stock_quantity'] < 5): ?>
                            <span class="badge bg-warning position-absolute" style="top: 10px; left: 10px;">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($product['price'] < 30): ?>
                            <span class="badge bg-success position-absolute" style="top: 10px; right: 10px;">
                                <i class="fas fa-tag"></i> Sale
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <!-- Category -->
                        <div class="mb-2">
                            <span class="badge bg-secondary"><?php echo $product['category'] ?? 'General'; ?></span>
                        </div>
                        
                        <!-- Product Name -->
                        <h5 class="card-title">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h5>
                        
                        <!-- Product Description -->
                        <p class="card-text text-muted flex-grow-1">
                            <?php 
                            $description = htmlspecialchars($product['description']);
                            echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                            ?>
                        </p>
                        
                        <!-- Price and Stock -->
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 text-primary fw-bold mb-0">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </span>
                                <small class="text-<?php echo $stock_class; ?>">
                                    <i class="fas fa-box"></i> <?php echo $stock_text; ?>
                                </small>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                                
                                <?php if (isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-success add-to-cart" 
                                   data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </a>
                                <?php elseif (!isLoggedIn()): ?>
                                <a href="login.php?redirect=product_detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login to Purchase
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-times-circle me-1"></i> Out of Stock
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick View Footer -->
                    <div class="card-footer bg-transparent border-top-0 pt-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="far fa-calendar me-1"></i>
                                <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                            </small>
                            <small>
                                <i class="far fa-heart text-muted"></i>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination (Optional) -->
        <nav aria-label="Product pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Add to Cart AJAX Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effect to product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
        });
    });
    
    // Image hover zoom
    const productImages = document.querySelectorAll('.product-image');
    productImages.forEach(img => {
        img.parentElement.addEventListener('mouseenter', function() {
            img.style.transform = 'scale(1.1)';
        });
        
        img.parentElement.addEventListener('mouseleave', function() {
            img.style.transform = 'scale(1)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>