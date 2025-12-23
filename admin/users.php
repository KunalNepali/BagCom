<?php
require_once 'bagcom_db';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - BagCom Admin</title>
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
        
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-bar button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .users-table {
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
        
        .active { background: #d4edda; color: #155724; }
        .inactive { background: #f8d7da; color: #721c24; }
        
        .action-btn { 
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .edit-btn { background: #ffc107; color: black; }
        .delete-btn { background: #dc3545; color: white; }
        .view-btn { background: #17a2b8; color: white; }
        
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
                <li><a href="orders.php">Orders</a></li>
                <li class="active"><a href="users.php">Users</a></li>
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
                <h1>Users Management</h1>
                <div>
                    <button class="action-btn" onclick="exportUsers()">Export CSV</button>
                    <button class="action-btn" onclick="addNewUser()">Add New User</button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search users by name, email, or phone...">
                <button onclick="searchUsers()">Search</button>
                <button onclick="clearSearch()">Clear</button>
            </div>
            
            <!-- Users Table -->
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 50";
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($user = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>#" . $user['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['phone'] ?? 'N/A') . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>";
                                $status = $user['status'] ?? 'active';
                                echo "<td><span class='status " . $status . "'>" . ucfirst($status) . "</span></td>";
                                echo "<td>
                                        <button class='action-btn view-btn' onclick='viewUser(" . $user['id'] . ")'>View</button>
                                        <button class='action-btn edit-btn' onclick='editUser(" . $user['id'] . ")'>Edit</button>
                                        <button class='action-btn delete-btn' onclick='deleteUser(" . $user['id'] . ")'>Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;padding:40px;'>No users found</td></tr>";
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
    function searchUsers() {
        const searchTerm = document.getElementById('searchInput').value;
        if (searchTerm.trim() !== '') {
            window.location.href = 'users.php?search=' + encodeURIComponent(searchTerm);
        }
    }
    
    function clearSearch() {
        document.getElementById('searchInput').value = '';
        window.location.href = 'users.php';
    }
    
    function addNewUser() {
        window.location.href = 'add_user.php';
    }
    
    function viewUser(userId) {
        window.location.href = 'user_details.php?id=' + userId;
    }
    
    function editUser(userId) {
        window.location.href = 'edit_user.php?id=' + userId;
    }
    
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'delete_user.php?id=' + userId;
        }
    }
    
    function exportUsers() {
        window.location.href = 'export_users.php';
    }
    </script>
</body>
</html>