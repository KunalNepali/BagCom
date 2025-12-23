<?php
require_once 'bagcom_db';

// Get order status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - BagCom Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar { 
            width: 250px; 
            background: #2c3e50; 
            color: white;
            padding: 20px 0;
        }
        
        .sidebar h2 { 
            padding: 0 20px 20px; 
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .sidebar ul { list-style: none; }
        .sidebar ul li a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar ul li a:hover, .sidebar ul li.active a {
            background: #34495e;
            border-left: 4px solid #3498db;
        }
        
        .main-content { flex: 1; padding: 20px; }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .filters { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px;
        }
        
        .filter-btn { 
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            background: #e0e0e0;
        }
        
        .filter-btn.active { 
            background: #3498db; 
            color: white;
        }
        
        .orders-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        
        .status { 
            padding: 5px 10px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: bold;
        }
        
        .pending { background: #fff3cd; color: #856404; }
        .processing { background: #cce5ff; color: #004085; }
        .completed { background: #d4edda; color: #155724; }
        .cancelled { background: #f8d7da; color: #721c24; }
        
        .action-btn { 
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .view-btn { background: #17a2b8; color: white; }
        .edit-btn { background: #ffc107; color: black; }
        .delete-btn { background: #dc3545; color: white; }
        
        .pagination { 
            display: flex; 
            justify-content: center; 
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-btn { 
            padding: 5px 10px; 
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }
        
        .page-btn.active { 
            background: #3498db; 
            color: white;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>BagCom Admin</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li class="active"><a href="orders.php">Orders</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="reviews.php">Reviews</a></li>
                <li><a href="sales_report.php">Sales Report</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="backup.php">Backup</a></li>
                <li><a href="../index.php" style="color: #e74c3c;">View Store</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Orders Management</h1>
                <div>
                    <button class="action-btn" onclick="window.print()">Print</button>
                    <button class="action-btn">Export</button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>" 
                        onclick="window.location.href='orders.php'">All Orders</button>
                <button class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" 
                        onclick="window.location.href='orders.php?status=pending'">Pending</button>
                <button class="filter-btn <?php echo $status_filter == 'processing' ? 'active' : ''; ?>" 
                        onclick="window.location.href='orders.php?status=processing'">Processing</button>
                <button class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>" 
                        onclick="window.location.href='orders.php?status=completed'">Completed</button>
                <button class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>" 
                        onclick="window.location.href='orders.php?status=cancelled'">Cancelled</button>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build query based on filter
                        $query = "SELECT * FROM orders";
                        if ($status_filter != 'all') {
                            $query .= " WHERE status='$status_filter'";
                        }
                        $query .= " ORDER BY order_date DESC LIMIT 50";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($order = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>#" . $order['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($order['order_date'])) . "</td>";
                                echo "<td>$" . number_format($order['total_amount'], 2) . "</td>";
                                echo "<td><span class='status " . $order['status'] . "'>" . ucfirst($order['status']) . "</span></td>";
                                echo "<td>" . ucfirst($order['payment_method']) . "</td>";
                                echo "<td>
                                        <button class='action-btn view-btn' onclick='viewOrder(" . $order['id'] . ")'>View</button>
                                        <button class='action-btn edit-btn' onclick='editOrder(" . $order['id'] . ")'>Edit</button>
                                        <button class='action-btn delete-btn' onclick='deleteOrder(" . $order['id'] . ")'>Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;padding:40px;'>No orders found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination">
                <button class="page-btn">Previous</button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">Next</button>
            </div>
        </div>
    </div>
    
    <script>
    function viewOrder(orderId) {
        window.location.href = 'order_details.php?id=' + orderId;
    }
    
    function editOrder(orderId) {
        window.location.href = 'edit_order.php?id=' + orderId;
    }
    
    function deleteOrder(orderId) {
        if (confirm('Are you sure you want to delete this order?')) {
            window.location.href = 'delete_order.php?id=' + orderId;
        }
    }
    </script>
</body>
</html>