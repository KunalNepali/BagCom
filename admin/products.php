<?php
require_once '../config/admin_auth.php';
requireAdminLogin();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = sanitize($_POST['bulk_action']);
    $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
    
    if (!empty($product_ids)) {
        $conn = getConnection();
        $ids_string = implode(',', array_map('intval', $product_ids));
        
        switch ($action) {
            case 'delete':
                // Delete products and their images
                foreach ($product_ids as $id) {
                    // Get image name before deleting
                    $sql = "SELECT image FROM products WHERE id = '$id'";
                    $result = mysqli_query($conn, $sql);
                    if ($row = mysqli_fetch_assoc($result)) {
                        if ($row['image'] != 'default.jpg') {
                            $image_path = "../uploads/products/" . $row['image'];
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                    }
                }
                
                $sql = "DELETE FROM products WHERE id IN ($ids_string)";
                mysqli_query($conn, $sql);
                logAdminAction('BULK_DELETE_PRODUCTS', "Deleted products: $ids_string");
                $_SESSION['success'] = count($product_ids) . " products deleted successfully!";
                break;
                
            case 'activate':
                $sql = "UPDATE products SET is_active = 1 WHERE id IN ($ids_string)";
                mysqli_query($conn, $sql);
                logAdminAction('BULK_ACTIVATE_PRODUCTS', "Activated products: $ids_string");
                $_SESSION['success'] = count($product_ids) . " products activated!";
                break;
                
            case 'deactivate':
                $sql = "UPDATE products SET is_active = 0 WHERE id IN ($ids_string)";
                mysqli_query($conn, $sql);
                logAdminAction('BULK_DEACTIVATE_PRODUCTS', "Deactivated products: $ids_string");
                $_SESSION['success'] = count($product_ids) . " products deactivated!";
                break;
        }
        
        closeConnection($conn);
        header('Location: products.php');
        exit();
    }
}

// Get all products with pagination
$conn = getConnection();
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$stock_filter = isset($_GET['stock']) ? sanitize($_GET['stock']) : '';

$where = "1=1";
if ($search) {
    $where .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category) {
    $where .= " AND category = '$category'";
}
if ($stock_filter) {
    switch ($stock_filter) {
        case 'in_stock':
            $where .= " AND stock_quantity > 0";
            break;
        case 'out_of_stock':
            $where .= " AND stock_quantity <= 0";
            break;
        case 'low_stock':
            $where .= " AND stock_quantity > 0 AND stock_quantity <= 10";
            break;
    }
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products WHERE $where";
$count_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get products
$sql = "SELECT * FROM products WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// Get categories for filter
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$categories_result = mysqli_query($conn, $categories_sql);

closeConnection($conn);

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Product Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add_product.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $cat['category']; ?>" 
                                    <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                <?php echo $cat['category']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="stock">
                        <option value="">All Stock</option>
                        <option value="in_stock" <?php echo ($stock_filter == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                        <option value="out_of_stock" <?php echo ($stock_filter == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                        <option value="low_stock" <?php echo ($stock_filter == 'low_stock') ? 'selected' : ''; ?>>Low Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <form method="POST" action="" id="bulkForm">
        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" name="bulk_action" id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-warning w-100" onclick="applyBulkAction()">
                    Apply
                </button>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-muted">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> products
                </span>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th width="80">Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($product = mysqli_fetch_assoc($result)): 
                                    $image_path = '../uploads/products/' . $product['image'];
                                    $image_url = file_exists($image_path) ? $image_path : '../uploads/products/default.jpg';
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td>
                                        <img src="<?php echo $image_url; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="img-thumbnail" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small class="text-muted">SKU: PROD-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $product['category'] ?? 'Uncategorized'; ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">$<?php echo number_format($product['price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $product['stock_quantity'] > 10 ? 'success' : 
                                                 ($product['stock_quantity'] > 0 ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo $product['stock_quantity']; ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($product['is_active'])): ?>
                                            <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($product['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../product_detail.php?id=<?php echo $product['id']; ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info" 
                                               title="View on Store">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               title="Delete"
                                               onclick="return confirm('Delete this product?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <h5>No products found</h5>
                                        <p class="text-muted">Try adjusting your search or add new products.</p>
                                        <a href="add_product.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add Your First Product
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Product pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category; ?>&stock=<?php echo $stock_filter; ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&category=<?php echo $category; ?>&stock=<?php echo $stock_filter; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category; ?>&stock=<?php echo $stock_filter; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
// Bulk selection
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Bulk action confirmation
function applyBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    
    if (!action) {
        alert('Please select a bulk action.');
        return;
    }
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one product.');
        return;
    }
    
    if (action === 'delete') {
        if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} product(s)? This action cannot be undone.`)) {
            return;
        }
    }
    
    document.getElementById('bulkForm').submit();
}
</script>

<?php include 'includes/admin_footer.php'; ?>