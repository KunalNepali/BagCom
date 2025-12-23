<?php
require_once '../config/admin_auth.php';
requireAdminLogin();

$categories = ['Backpacks', 'Travel', 'Office', 'Outdoor', 'Luggage', 'Accessories', 'Other'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $category = sanitize($_POST['category']);
    
    // Handle image upload
    $image_name = 'default.jpg';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_name = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            // Generate unique filename
            $image_name = 'product_' . time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/products/' . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Image uploaded successfully
                logAdminAction('UPLOAD_PRODUCT_IMAGE', "Uploaded: $image_name");
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                $image_name = 'default.jpg';
            }
        } else {
            $_SESSION['error'] = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP";
        }
    }
    
    $conn = getConnection();
    
    $sql = "INSERT INTO products (name, description, price, image, stock_quantity, category) 
            VALUES ('$name', '$description', '$price', '$image_name', '$stock_quantity', '$category')";
    
    if (mysqli_query($conn, $sql)) {
        $product_id = mysqli_insert_id($conn);
        logAdminAction('ADD_PRODUCT', "Added product: $name (ID: $product_id)");
        $_SESSION['success'] = "Product added successfully!";
        header('Location: products.php');
        exit();
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    
    closeConnection($conn);
}

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Product</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">
                                <strong>Supported formats:</strong> JPG, PNG, GIF, WEBP (Max 5MB)<br>
                                <strong>Optimal size:</strong> 800×800 pixels
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="mb-3">
                            <label class="form-label">Image Preview</label>
                            <div id="imagePreview" class="border rounded p-3 text-center" 
                                 style="min-height: 200px; background: #f8f9fa; cursor: pointer;" 
                                 onclick="document.getElementById('image').click()">
                                <div class="text-muted" id="previewPlaceholder">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i><br>
                                    Click to upload or drag & drop image
                                </div>
                                <img id="previewImage" src="" alt="Preview" 
                                     class="img-fluid mt-2" style="max-height: 180px; display: none;">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Product
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Product Guidelines</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> Tips for Better Products</h6>
                        <ul class="mb-0">
                            <li>Use clear, high-quality images</li>
                            <li>Write detailed descriptions</li>
                            <li>Set competitive pricing</li>
                            <li>Choose appropriate categories</li>
                            <li>Update stock regularly</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Important Notes</h6>
                        <ul class="mb-0">
                            <li>All fields marked with * are required</li>
                            <li>Images will be automatically optimized</li>
                            <li>Products are immediately visible on site</li>
                            <li>Review all details before saving</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <img src="../assets/images/product-guidelines.png" 
                             alt="Product Guidelines" 
                             class="img-fluid rounded" 
                             style="max-height: 150px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview functionality
document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('previewImage');
    const placeholder = document.getElementById('previewPlaceholder');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
});

// Drag and drop for image
const previewArea = document.getElementById('imagePreview');

previewArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.background = '#e9ecef';
});

previewArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.background = '#f8f9fa';
});

previewArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.background = '#f8f9fa';
    
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        document.getElementById('image').files = e.dataTransfer.files;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('previewImage');
            const placeholder = document.getElementById('previewPlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>