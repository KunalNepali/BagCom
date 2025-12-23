<?php
require_once 'db_connection.php';

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - BagCom Admin</title>
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
        
        .reviews-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        
        .rating { color: #ffc107; font-weight: bold; }
        
        .review-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .status { 
            padding: 5px 10px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: bold;
        }
        
        .approved { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }
        .rejected { background: #f8d7da; color: #721c24; }
        
        .action-btn { 
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .approve-btn { background: #28a745; color: white; }
        .reject-btn { background: #dc3545; color: white; }
        .edit-btn { background: #ffc107; color: black; }
        .delete-btn { background: #6c757d; color: white; }
        
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
        
        .stars {
            color: #ffc107;
            font-size: 18px;
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
                <li><a href="orders.php">Orders</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li class="active"><a href="reviews.php">Reviews</a></li>
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
                <h1>Reviews Management</h1>
                <div>
                    <button class="action-btn" onclick="window.print()">Print</button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>" 
                        onclick="window.location.href='reviews.php'">All Reviews</button>
                <button class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>" 
                        onclick="window.location.href='reviews.php?filter=pending'">Pending</button>
                <button class="filter-btn <?php echo $filter == 'approved' ? 'active' : ''; ?>" 
                        onclick="window.location.href='reviews.php?filter=approved'">Approved</button>
                <button class="filter-btn <?php echo $filter == 'rejected' ? 'active' : ''; ?>" 
                        onclick="window.location.href='reviews.php?filter=rejected'">Rejected</button>
            </div>
            
            <!-- Reviews Table -->
            <div class="reviews-table">
                <table>
                    <thead>
                        <tr>
                            <th>Review ID</th>
                            <th>Product</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build query based on filter
                        $query = "SELECT r.*, p.name as product_name, u.name as user_name 
                                  FROM reviews r 
                                  JOIN products p ON r.product_id = p.id 
                                  JOIN users u ON r.user_id = u.id";
                        
                        if ($filter != 'all') {
                            $query .= " WHERE r.status='$filter'";
                        }
                        
                        $query .= " ORDER BY r.created_at DESC LIMIT 50";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($review = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>#" . $review['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($review['product_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($review['user_name']) . "</td>";
                                
                                // Display stars
                                $rating = $review['rating'];
                                echo "<td>";
                                echo "<span class='stars'>";
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo "★";
                                    } else {
                                        echo "☆";
                                    }
                                }
                                echo "</span> ($rating/5)";
                                echo "</td>";
                                
                                echo "<td class='review-content' title='" . htmlspecialchars($review['comment']) . "'>" 
                                     . htmlspecialchars(substr($review['comment'], 0, 50)) . "..." . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($review['created_at'])) . "</td>";
                                echo "<td><span class='status " . $review['status'] . "'>" . ucfirst($review['status']) . "</span></td>";
                                echo "<td>";
                                if ($review['status'] == 'pending') {
                                    echo "<button class='action-btn approve-btn' onclick='approveReview(" . $review['id'] . ")'>Approve</button>";
                                    echo "<button class='action-btn reject-btn' onclick='rejectReview(" . $review['id'] . ")'>Reject</button>";
                                }
                                echo "<button class='action-btn edit-btn' onclick='editReview(" . $review['id'] . ")'>Edit</button>";
                                echo "<button class='action-btn delete-btn' onclick='deleteReview(" . $review['id'] . ")'>Delete</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center;padding:40px;'>No reviews found</td></tr>";
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
    function approveReview(reviewId) {
        if (confirm('Approve this review?')) {
            window.location.href = 'approve_review.php?id=' + reviewId;
        }
    }
    
    function rejectReview(reviewId) {
        if (confirm('Reject this review?')) {
            window.location.href = 'reject_review.php?id=' + reviewId;
        }
    }
    
    function editReview(reviewId) {
        window.location.href = 'edit_review.php?id=' + reviewId;
    }
    
    function deleteReview(reviewId) {
        if (confirm('Are you sure you want to delete this review?')) {
            window.location.href = 'delete_review.php?id=' + reviewId;
        }
    }
    </script>
</body>
</html>