<?php
// Admin-specific functions for BagCom admin panel

/**
 * Get total number of pending orders
 */
function getPendingOrdersCount() {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    
    closeConnection($conn);
    return $data['count'] ?? 0;
}

/**
 * Get total revenue for a specific period
 */
function getRevenue($period = 'today') {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $where = '';
    switch ($period) {
        case 'today':
            $where = "DATE(created_at) = CURDATE()";
            break;
        case 'this_week':
            $where = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
            break;
        case 'this_month':
            $where = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        case 'this_year':
            $where = "YEAR(created_at) = YEAR(CURDATE())";
            break;
        default:
            $where = "1=1";
    }
    
    $sql = "SELECT SUM(total_amount) as revenue FROM orders WHERE status IN ('processing', 'shipped', 'delivered') AND $where";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    
    closeConnection($conn);
    return $data['revenue'] ?? 0;
}

/**
 * Get low stock products
 */
function getLowStockProducts($limit = 5) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT * FROM products WHERE stock_quantity > 0 AND stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    $products = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    closeConnection($conn);
    return $products;
}

/**
 * Get recent orders with user info
 */
function getRecentOrders($limit = 10) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT o.*, u.username, u.email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    
    closeConnection($conn);
    return $orders;
}

/**
 * Get best selling products
 */
function getBestSellingProducts($limit = 5) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT p.id, p.name, p.image, p.price, 
                   SUM(oi.quantity) as total_sold,
                   SUM(oi.quantity * oi.price) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $products = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    closeConnection($conn);
    return $products;
}

/**
 * Get order statistics by status
 */
function getOrderStats() {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as revenue
            FROM orders 
            GROUP BY status";
    
    $result = mysqli_query($conn, $sql);
    $stats = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $stats[$row['status']] = $row;
    }
    
    closeConnection($conn);
    return $stats;
}

/**
 * Get monthly sales data for chart
 */
function getMonthlySalesData($months = 6) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC";
    
    $result = mysqli_query($conn, $sql);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    closeConnection($conn);
    return $data;
}

/**
 * Get user registration statistics
 */
function getUserRegistrationStats($days = 30) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as registrations
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
    
    $result = mysqli_query($conn, $sql);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    closeConnection($conn);
    return $data;
}

/**
 * Delete product with its image
 */
function deleteProductWithImage($product_id) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    // Get image name before deleting
    $sql = "SELECT image FROM products WHERE id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $image_name = $row['image'];
        
        // Delete product
        $sql = "DELETE FROM products WHERE id = '$product_id'";
        $delete_result = mysqli_query($conn, $sql);
        
        if ($delete_result) {
            // Delete image file if not default
            if ($image_name != 'default.jpg') {
                $image_path = "../uploads/products/" . $image_name; // FIXED PATH
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            return true;
        }
    }
    
    closeConnection($conn);
    return false;
}

/**
 * Update product stock
 */
function updateProductStock($product_id, $quantity) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    
    closeConnection($conn);
    return $result;
}

/**
 * Get order details with items
 */
function getOrderDetails($order_id) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    // Get order info
    $sql = "SELECT o.*, u.username, u.email, u.phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = '$order_id'";
    $result = mysqli_query($conn, $sql);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        closeConnection($conn);
        return null;
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // Get order items
    $sql = "SELECT oi.*, p.name, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = '$order_id'";
    $items_result = mysqli_query($conn, $sql);
    $items = [];
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }
    
    $order['items'] = $items;
    
    closeConnection($conn);
    return $order;
}

/**
 * Update order status
 */
function updateOrderStatus($order_id, $status) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "UPDATE orders SET status = '$status' WHERE id = '$order_id'";
    $result = mysqli_query($conn, $sql);
    
    closeConnection($conn);
    return $result;
}

/**
 * Get total customers count
 */
function getTotalCustomers() {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    
    closeConnection($conn);
    return $data['count'] ?? 0;
}

/**
 * Get today's sales
 */
function getTodaySales() {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT COUNT(*) as orders, SUM(total_amount) as revenue 
            FROM orders 
            WHERE DATE(created_at) = CURDATE() AND status IN ('processing', 'shipped', 'delivered')";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    
    closeConnection($conn);
    return $data;
}

/**
 * Get admin activity logs
 */
function getAdminActivityLogs($admin_id = null, $limit = 50) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $where = $admin_id ? "WHERE al.admin_id = '$admin_id'" : "";
    $sql = "SELECT al.*, a.username 
            FROM admin_logs al 
            LEFT JOIN admins a ON al.admin_id = a.id 
            $where 
            ORDER BY al.created_at DESC 
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    closeConnection($conn);
    return $logs;
}

/**
 * Get product categories with counts
 */
function getProductCategories() {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT category, COUNT(*) as count 
            FROM products 
            WHERE category IS NOT NULL AND category != '' 
            GROUP BY category 
            ORDER BY count DESC";
    
    $result = mysqli_query($conn, $sql);
    $categories = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    closeConnection($conn);
    return $categories;
}

/**
 * Generate sales report data
 */
function generateSalesReport($start_date, $end_date, $report_type = 'daily') {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    switch ($report_type) {
        case 'daily':
            $group_by = "DATE(created_at)";
            break;
        case 'weekly':
            $group_by = "YEARWEEK(created_at)";
            break;
        case 'monthly':
            $group_by = "DATE_FORMAT(created_at, '%Y-%m')";
            break;
        case 'yearly':
            $group_by = "YEAR(created_at)";
            break;
        default:
            $group_by = "DATE(created_at)";
    }
    
    $sql = "SELECT 
                $group_by as period,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order_value,
                MIN(total_amount) as min_order,
                MAX(total_amount) as max_order
            FROM orders 
            WHERE created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
            GROUP BY $group_by
            ORDER BY period DESC";
    
    $result = mysqli_query($conn, $sql);
    $report = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $report[] = $row;
    }
    
    closeConnection($conn);
    return $report;
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename = 'export.csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Check if product exists
 */
function productExists($product_id) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT id FROM products WHERE id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    $exists = mysqli_num_rows($result) > 0;
    
    closeConnection($conn);
    return $exists;
}

/**
 * Get product by ID with detailed info
 */
function getProductDetails($product_id) {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $sql = "SELECT p.*, 
                   (SELECT SUM(quantity) FROM order_items WHERE product_id = p.id) as total_sold,
                   (SELECT COUNT(*) FROM order_items WHERE product_id = p.id) as times_ordered
            FROM products p 
            WHERE p.id = '$product_id'";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    } else {
        $product = null;
    }
    
    closeConnection($conn);
    return $product;
}

/**
 * Get all products with pagination
 */
function getAllProductsPaginated($page = 1, $limit = 10, $search = '', $category = '') {
    require_once '../config/database.php'; // FIXED PATH
    $conn = getConnection();
    
    $offset = ($page - 1) * $limit;
    $where = "1=1";
    
    if (!empty($search)) {
        $where .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
    }
    
    if (!empty($category)) {
        $where .= " AND category = '$category'";
    }
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE $where";
    $count_result = mysqli_query($conn, $count_sql);
    $total_data = mysqli_fetch_assoc($count_result);
    $total_pages = ceil($total_data['total'] / $limit);
    
    // Get products
    $sql = "SELECT * FROM products WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);
    $products = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    closeConnection($conn);
    
    return [
        'products' => $products,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_items' => $total_data['total']
    ];
}

/**
 * Validate admin input
 */
function validateAdminInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';
        
        // Required validation
        if (strpos($rule, 'required') !== false && empty(trim($value))) {
            $errors[$field] = ucfirst($field) . " is required.";
            continue;
        }
        
        // Email validation
        if (strpos($rule, 'email') !== false && !empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Please enter a valid email address.";
            }
        }
        
        // Numeric validation
        if (strpos($rule, 'numeric') !== false && !empty($value)) {
            if (!is_numeric($value)) {
                $errors[$field] = ucfirst($field) . " must be a number.";
            }
        }
        
        // Min length validation
        if (preg_match('/min:(\d+)/', $rule, $matches)) {
            $min_length = $matches[1];
            if (strlen($value) < $min_length) {
                $errors[$field] = ucfirst($field) . " must be at least $min_length characters.";
            }
        }
        
        // Max length validation
        if (preg_match('/max:(\d+)/', $rule, $matches)) {
            $max_length = $matches[1];
            if (strlen($value) > $max_length) {
                $errors[$field] = ucfirst($field) . " cannot exceed $max_length characters.";
            }
        }
    }
    
    return $errors;
}

/**
 * Sanitize admin input
 */
function sanitizeAdminInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeAdminInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'F j, Y, g:i a') {
    return date($format, strtotime($date));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badge_class = '';
    
    switch ($status) {
        case 'pending':
            $badge_class = 'warning';
            break;
        case 'processing':
            $badge_class = 'info';
            break;
        case 'shipped':
            $badge_class = 'primary';
            break;
        case 'delivered':
            $badge_class = 'success';
            break;
        case 'cancelled':
            $badge_class = 'danger';
            break;
        default:
            $badge_class = 'secondary';
    }
    
    return '<span class="badge bg-' . $badge_class . '">' . ucfirst($status) . '</span>';
}

/**
 * Get stock level badge HTML
 */
function getStockBadge($quantity) {
    if ($quantity <= 0) {
        return '<span class="badge bg-danger">Out of Stock</span>';
    } elseif ($quantity <= 10) {
        return '<span class="badge bg-warning">Low Stock (' . $quantity . ')</span>';
    } else {
        return '<span class="badge bg-success">In Stock (' . $quantity . ')</span>';
    }
}

/**
 * Redirect with message
 */
function adminRedirect($url, $message_type = '', $message = '') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($message_type && $message) {
        $_SESSION[$message_type] = $message;
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Check if admin has permission
 */
function checkAdminPermission($permission) {
    require_once '../config/admin_auth.php'; // FIXED PATH
    return hasAdminPermission($permission);
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Send admin notification email (placeholder)
 */
function sendAdminNotification($subject, $message, $to = null) {
    // This is a placeholder function
    // In a real application, you would implement email sending here
    error_log("Admin Notification: $subject - $message");
    return true;
}

/**
 * Backup database (placeholder)
 */
function backupDatabase() {
    // This is a placeholder function
    // In a real application, you would implement database backup here
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    error_log("Database backup created: $backup_file");
    return $backup_file;
}

/**
 * Clean up old files
 */
function cleanupOldFiles($directory, $days_old = 30) {
    $files = glob($directory . '/*');
    $deleted_count = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < strtotime("-$days_old days")) {
            unlink($file);
            $deleted_count++;
        }
    }
    
    return $deleted_count;
}
?>