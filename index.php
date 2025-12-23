<?php
require_once 'config/functions.php';
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4">Welcome to BagCom</h1>
        <p class="lead">Find the perfect bag for every occasion</p>
        <a href="products.php" class="btn btn-light btn-lg">Shop Now</a>
    </div>
</section>

<!-- Featured Products -->
<section class="products py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        <div class="row">
            <?php
            $products = getAllProducts(4);
            foreach ($products as $product): 
            ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="uploads/products/<?php echo $product['image'] ?? 'default.jpg'; ?>" 
                         class="card-img-top" alt="<?php echo $product['name']; ?>" 
                         style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo substr($product['description'], 0, 100); ?>...</p>
                        <p class="card-text"><strong>$<?php echo $product['price']; ?></strong></p>
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                        <?php if (isLoggedIn()): ?>
                        <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-success">Add to Cart</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="products.php" class="btn btn-outline-primary">View All Products</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>