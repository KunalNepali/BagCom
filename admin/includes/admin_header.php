<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/admin_auth.php';
requireAdminLogin();

$admin = getCurrentAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BagCom Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            font-size: 0.875rem;
            background-color: #f8f9fc;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: var(--primary-color);
            background-color: #f8f9fa;
            border-left-color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            color: var(--primary-color);
            background-color: #f8f9fa;
            border-left-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        
        .card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .badge-pill {
            border-radius: 10rem;
            padding: 0.25em 0.6em;
        }
        
        .alert {
            border: none;
            border-radius: 0.35rem;
        }
        
        .btn {
            border-radius: 0.35rem;
            font-weight: 500;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--secondary-color);
        }
        
        .stat-card .card-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                height: auto;
                padding-top: 0;
            }
            
            .sidebar-sticky {
                height: auto;
            }
            
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark bg-dark fixed-top flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
            <i class="fas fa-shopping-bag me-2"></i> BagCom Admin
        </a>
        
        <!-- Admin Info & Dropdown -->
        <div class="navbar-nav ms-auto px-3">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                    <div class="admin-avatar me-2">
                        <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?php echo $admin['username'] ?? 'Admin'; ?></div>
                        <small class="text-light"><?php echo ucfirst($admin['admin_type'] ?? 'admin'); ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="../index.php" target="_blank">
                            <i class="fas fa-store me-2"></i> View Store
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3 sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>"
                               href="products.php">
                                <i class="fas fa-boxes"></i> Products
                                <span class="badge bg-primary rounded-pill float-end">
                                    <?php
                                    require_once 'admin_functions.php';
                                    $products = getAllProductsPaginated(1, 1);
                                    echo $products['total_items'] ?? 0;
                                    ?>
                                </span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order_detail.php']) ? 'active' : ''; ?>"
                               href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                                <span class="badge bg-warning rounded-pill float-end">
                                    <?php echo getPendingOrdersCount(); ?>
                                </span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : ''; ?>"
                               href="sales_report.php">
                                <i class="fas fa-chart-bar"></i> Sales Reports
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"
                               href="users.php">
                                <i class="fas fa-users"></i> Customers
                                <span class="badge bg-info rounded-pill float-end">
                                    <?php echo getTotalCustomers(); ?>
                                </span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>"
                               href="reviews.php">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"
                               href="categories.php">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                               href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>"
                               href="backup.php">
                                <i class="fas fa-database"></i> Backup
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                        <span>Quick Links</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="add_product.php">
                                <i class="fas fa-plus-circle"></i> Add New Product
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php?stock=low_stock">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock Alert
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php?status=pending">
                                <i class="fas fa-clock"></i> Pending Orders
                            </a>
                        </li>
                    </ul>
                    
                    <!-- System Status -->
                    <div class="card border-0 bg-transparent mt-4">
                        <div class="card-body p-3">
                            <h6 class="card-title text-muted">System Status</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <small>Orders Today:</small>
                                <small class="fw-bold"><?php echo getTodaySales()['orders'] ?? 0; ?></small>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small>Revenue Today:</small>
                                <small class="fw-bold text-success">$<?php echo number_format(getTodaySales()['revenue'] ?? 0, 2); ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small>Uptime:</small>
                                <small class="fw-bold text-success">100%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="padding-top: 20px;">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                        <?php
                        // Dynamic breadcrumb based on current page
                        $page = basename($_SERVER['PHP_SELF'], '.php');
                        $page_titles = [
                            'dashboard' => 'Dashboard',
                            'products' => 'Products',
                            'add_product' => 'Add Product',
                            'edit_product' => 'Edit Product',
                            'orders' => 'Orders',
                            'order_detail' => 'Order Details',
                            'sales_report' => 'Sales Report',
                            'users' => 'Customers',
                            'reviews' => 'Reviews',
                            'categories' => 'Categories',
                            'settings' => 'Settings',
                            'backup' => 'Backup'
                        ];
                        
                        if (isset($page_titles[$page])) {
                            echo '<li class="breadcrumb-item active">' . $page_titles[$page] . '</li>';
                        }
                        ?>
                    </ol>
                </nav>
                
                <!-- Page Content Starts Here -->