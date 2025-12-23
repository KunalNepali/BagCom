<?php
require_once '../config/admin_auth.php';
requireAdminLogin();

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';

$conn = getConnection();

// Get sales data based on report type
switch ($report_type) {
    case 'daily':
        $sql = "SELECT 
                    DATE(created_at) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                GROUP BY DATE(created_at)
                ORDER BY period DESC";
        break;
        
    case 'weekly':
        $sql = "SELECT 
                    YEARWEEK(created_at) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                GROUP BY YEARWEEK(created_at)
                ORDER BY period DESC";
        break;
        
    case 'monthly':
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY period DESC";
        break;
        
    case 'yearly':
        $sql = "SELECT 
                    YEAR(created_at) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                GROUP BY YEAR(created_at)
                ORDER BY period DESC";
        break;
}

$sales_result = mysqli_query($conn, $sql);

// Get top products
$top_products_sql = "SELECT 
                        p.name,
                        p.image,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.quantity * oi.price) as revenue
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                     GROUP BY p.id
                     ORDER BY total_sold DESC
                     LIMIT 10";
$top_products_result = mysqli_query($conn, $top_products_sql);

// Get payment method distribution
$payment_methods_sql = "SELECT 
                           payment_method,
                           COUNT(*) as order_count,
                           SUM(total_amount) as revenue
                        FROM orders 
                        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                        GROUP BY payment_method";
$payment_methods_result = mysqli_query($conn, $payment_methods_sql);

closeConnection($conn);

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Sales Reports</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Summary -->
    <div class="row mb-4">
        <?php
        $total_revenue = 0;
        $total_orders = 0;
        while ($row = mysqli_fetch_assoc($sales_result)) {
            $total_revenue += $row['revenue'];
            $total_orders += $row['order_count'];
        }
        mysqli_data_seek($sales_result, 0);
        ?>
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($total_revenue, 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $total_orders; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Avg. Order Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Date Range</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo date('M d, Y', strtotime($start_date)); ?> - 
                                <?php echo date('M d, Y', strtotime($end_date)); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Data Table -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Data</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg. Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sale = mysqli_fetch_assoc($sales_result)): ?>
                                <tr>
                                    <td><?php echo $sale['period']; ?></td>
                                    <td><?php echo $sale['order_count']; ?></td>
                                    <td>$<?php echo number_format($sale['revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($sale['avg_order_value'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Top Products -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                <tr>
                                    <td>
                                        <small><?php echo htmlspecialchars($product['name']); ?></small>
                                    </td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td>$<?php echo number_format($product['revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Methods</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($method = mysqli_fetch_assoc($payment_methods_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                    <td><?php echo $method['order_count']; ?></td>
                                    <td>$<?php echo number_format($method['revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card shadow mt-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Export Report</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <button class="btn btn-success w-100" onclick="exportToCSV()">
                        <i class="fas fa-file-csv"></i> Export as CSV
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-danger w-100" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-primary w-100" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export as Excel
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-secondary w-100" onclick="emailReport()">
                        <i class="fas fa-envelope"></i> Email Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Export functions (demo - would need backend implementation)
function exportToCSV() {
    alert('CSV export would be implemented with server-side processing');
    // window.location.href = 'export.php?type=csv&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>';
}

function exportToPDF() {
    alert('PDF export would be implemented with server-side processing');
    // window.location.href = 'export.php?type=pdf&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>';
}

function exportToExcel() {
    alert('Excel export would be implemented with server-side processing');
    // window.location.href = 'export.php?type=excel&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>';
}

function emailReport() {
    const email = prompt('Enter email address to send report:');
    if (email) {
        alert(`Report would be sent to ${email}`);
        // AJAX call to send email
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>