<?php
// Add error reporting at the TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/admin_auth.php';
requireAdminLogin();

// Get dashboard statistics
require_once '../config/database.php';
$conn = getConnection();

// 1. Total sales stats
$sales_sql = "SELECT 
                COUNT(*) as total_orders, 
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                MIN(total_amount) as min_order,
                MAX(total_amount) as max_order
              FROM orders 
              WHERE status IN ('processing', 'shipped', 'delivered')";
$sales_result = mysqli_query($conn, $sales_sql);
$sales_data = mysqli_fetch_assoc($sales_result);

// 2. Product statistics
$products_sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(stock_quantity) as total_stock,
                    SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock
                 FROM products";
$products_result = mysqli_query($conn, $products_sql);
$products_data = mysqli_fetch_assoc($products_result);

// 3. User statistics
$users_sql = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
              FROM users";
$users_result = mysqli_query($conn, $users_sql);
$users_data = mysqli_fetch_assoc($users_result);

// 4. Order status breakdown
$status_sql = "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as revenue
               FROM orders 
               GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row;
}

// 5. Recent orders (last 10)
$orders_sql = "SELECT o.*, u.username, u.email 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               ORDER BY o.created_at DESC 
               LIMIT 10";
$orders_result = mysqli_query($conn, $orders_sql);

// 6. Low stock products
$low_stock_sql = "SELECT * FROM products 
                  WHERE stock_quantity > 0 AND stock_quantity <= 10 
                  ORDER BY stock_quantity ASC 
                  LIMIT 5";
$low_stock_result = mysqli_query($conn, $low_stock_sql);

// 7. Best selling products
$best_selling_sql = "SELECT p.id, p.name, p.image, 
                            SUM(oi.quantity) as total_sold,
                            SUM(oi.quantity * oi.price) as revenue
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     GROUP BY p.id
                     ORDER BY total_sold DESC
                     LIMIT 5";
$best_selling_result = mysqli_query($conn, $best_selling_sql);

// 8. Today's stats
$today_sql = "SELECT 
                COUNT(*) as orders_today,
                SUM(total_amount) as revenue_today
              FROM orders 
              WHERE DATE(created_at) = CURDATE()";
$today_result = mysqli_query($conn, $today_sql);
$today_data = mysqli_fetch_assoc($today_result);

// 9. Monthly sales for chart
$monthly_sales_sql = "SELECT 
                        DATE_FORMAT(created_at, '%b') as month,
                        MONTH(created_at) as month_num,
                        COUNT(*) as order_count,
                        SUM(total_amount) as monthly_revenue
                      FROM orders 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b'), MONTH(created_at)
                      ORDER BY YEAR(created_at), MONTH(created_at)";
$monthly_sales_result = mysqli_query($conn, $monthly_sales_sql);

// Prepare chart data
$chart_months = [];
$chart_revenue = [];
$chart_orders = [];
while ($row = mysqli_fetch_assoc($monthly_sales_result)) {
    $chart_months[] = $row['month'];
    $chart_revenue[] = $row['monthly_revenue'] ?? 0;
    $chart_orders[] = $row['order_count'] ?? 0;
}

closeConnection($conn);

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportDashboard()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-calendar"></i> Last 30 Days
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?period=today">Today</a></li>
                    <li><a class="dropdown-item" href="?period=week">This Week</a></li>
                    <li><a class="dropdown-item" href="?period=month">This Month</a></li>
                    <li><a class="dropdown-item" href="?period=year">This Year</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($products_data['low_stock'] > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Alert:</strong> <?php echo $products_data['low_stock']; ?> products are low on stock.
        <a href="products.php?filter=low_stock" class="alert-link">View low stock items</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (($status_data['pending']['count'] ?? 0) > 0): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <i class="fas fa-clock me-2"></i>
        <strong>Pending Orders:</strong> <?php echo $status_data['pending']['count']; ?> orders need processing.
        <a href="orders.php?status=pending" class="alert-link">Review pending orders</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Quick Stats Row -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($sales_data['total_revenue'] ?? 0, 2); ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success mr-2">
                                    <i class="fas fa-arrow-up"></i> Today: $<?php echo number_format($today_data['revenue_today'] ?? 0, 2); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $sales_data['total_orders'] ?? 0; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-info mr-2">
                                    <i class="fas fa-shopping-cart"></i> Today: <?php echo $today_data['orders_today'] ?? 0; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $products_data['total_products'] ?? 0; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="<?php echo ($products_data['low_stock'] > 0) ? 'text-warning' : 'text-success'; ?> mr-2">
                                    <i class="fas fa-box"></i> Low Stock: <?php echo $products_data['low_stock'] ?? 0; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $users_data['total_users'] ?? 0; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-primary mr-2">
                                    <i class="fas fa-user-plus"></i> New Today: <?php echo $users_data['new_today'] ?? 0; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Sales Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Overview (Last 6 Months)</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            <div class="dropdown-header">Chart Options:</div>
                            <a class="dropdown-item" href="#" onclick="changeChartType('line')">Line Chart</a>
                            <a class="dropdown-item" href="#" onclick="changeChartType('bar')">Bar Chart</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="downloadChart()">Download Chart</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($status_data as $status => $data): ?>
                        <span class="mr-2">
                            <i class="fas fa-circle" style="color: <?php 
                                switch($status) {
                                    case 'pending': echo '#f6c23e'; break;
                                    case 'processing': echo '#36b9cc'; break;
                                    case 'shipped': echo '#4e73df'; break;
                                    case 'delivered': echo '#1cc88a'; break;
                                    case 'cancelled': echo '#e74a3b'; break;
                                    default: echo '#858796';
                                }
                            ?>"></i> <?php echo ucfirst($status); ?> (<?php echo $data['count'] ?? 0; ?>)
                        </span><br>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                    <tr>
                                        <td><a href="order_detail.php?id=<?php echo $order['id']; ?>">#<?php echo $order['order_number']; ?></a></td>
                                        <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                        <td><?php echo date('M d', strtotime($order['created_at'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'processing': echo 'info'; break;
                                                    case 'shipped': echo 'primary'; break;
                                                    case 'delivered': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">
                                            <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i><br>
                                            No orders found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock & Best Sellers -->
        <div class="col-xl-6 col-lg-6">
            <div class="row">
                <!-- Low Stock Products -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-warning">Low Stock Alert</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($low_stock_result && mysqli_num_rows($low_stock_result) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($product = mysqli_fetch_assoc($low_stock_result)): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between px-0">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted">Current Stock: <?php echo $product['stock_quantity']; ?> units</small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $product['stock_quantity'] <= 5 ? 'danger' : 'warning'; ?> me-2">
                                                <?php echo $product['stock_quantity']; ?> left
                                            </span>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="products.php?filter=low_stock" class="btn btn-warning btn-sm">Manage All Low Stock</a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-success">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <p class="mb-0">All products have sufficient stock</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Best Selling Products -->
                <div class="col-lg-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Best Selling Products</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($best_selling_result && mysqli_num_rows($best_selling_result) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($product = mysqli_fetch_assoc($best_selling_result)): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between px-0">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $image_path = '../uploads/products/' . $product['image'];
                                            $image_url = file_exists($image_path) ? $image_path : '../uploads/products/default.jpg';
                                            ?>
                                            <img src="<?php echo $image_url; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="rounded me-3" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted">Sold: <?php echo $product['total_sold']; ?> units</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-success fw-bold">$<?php echo number_format($product['revenue'], 2); ?></div>
                                            <small class="text-muted">Revenue</small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                                    <p class="mb-0">No sales data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mt-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h5>Pending Orders</h5>
                        <h2><?php echo $status_data['pending']['count'] ?? 0; ?></h2>
                        <small>$<?php echo number_format($status_data['pending']['revenue'] ?? 0, 2); ?> value</small>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="orders.php?status=pending" class="text-white">View All <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>Delivered Orders</h5>
                        <h2><?php echo $status_data['delivered']['count'] ?? 0; ?></h2>
                        <small>$<?php echo number_format($status_data['delivered']['revenue'] ?? 0, 2); ?> revenue</small>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="orders.php?status=delivered" class="text-white">View All <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-truck fa-2x mb-2"></i>
                        <h5>Shipped Orders</h5>
                        <h2><?php echo $status_data['shipped']['count'] ?? 0; ?></h2>
                        <small>$<?php echo number_format($status_data['shipped']['revenue'] ?? 0, 2); ?> value</small>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="orders.php?status=shipped" class="text-white">Track All <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h5>Out of Stock</h5>
                        <h2><?php echo $products_data['out_of_stock'] ?? 0; ?></h2>
                        <small><?php echo $products_data['low_stock'] ?? 0; ?> low stock items</small>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="products.php?filter=out_of_stock" class="text-white">Restock <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Sales Chart
let salesChart;
let orderStatusChart;

document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        salesChart = new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_months); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    tension: 0.1
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    backgroundColor: 'rgba(54, 185, 204, 0.05)',
                    borderColor: 'rgba(54, 185, 204, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Revenue')) {
                                    return label + ': $' + context.parsed.y.toLocaleString();
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });
    }

    // Order Status Pie Chart
    const orderCtx = document.getElementById('orderStatusChart');
    if (orderCtx) {
        orderStatusChart = new Chart(orderCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $status_data['pending']['count'] ?? 0; ?>,
                        <?php echo $status_data['processing']['count'] ?? 0; ?>,
                        <?php echo $status_data['shipped']['count'] ?? 0; ?>,
                        <?php echo $status_data['delivered']['count'] ?? 0; ?>,
                        <?php echo $status_data['cancelled']['count'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#f6c23e',
                        '#36b9cc',
                        '#4e73df',
                        '#1cc88a',
                        '#e74a3b'
                    ],
                    hoverBackgroundColor: [
                        '#f8d45c',
                        '#4cc5d8',
                        '#5c8ce6',
                        '#2cd699',
                        '#f85c5c'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
            },
        });
    }
});

// Chart functions
function changeChartType(type) {
    if (salesChart) {
        salesChart.config.type = type;
        salesChart.update();
    }
}

function downloadChart() {
    if (salesChart) {
        const link = document.createElement('a');
        link.download = 'sales-chart.png';
        link.href = salesChart.toBase64Image();
        link.click();
    }
}

function exportDashboard() {
    alert('Export feature would generate PDF/Excel report');
    // In real implementation, this would trigger a server-side export
}
</script>

<?php include 'includes/admin_footer.php'; ?>