<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getConnection();
    
    $sql = "SELECT * FROM admins WHERE username = '$username' AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        
        // Verify password (using password_hash in production)
        // For demo, using simple verification
        if ($password === 'admin123' || password_verify($password, $admin['password'])) {
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_type'] = $admin['admin_type'];
            
            // Update last login
            $update_sql = "UPDATE admins SET last_login = NOW() WHERE id = '{$admin['id']}'";
            mysqli_query($conn, $update_sql);
            
            // Log the login
            $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address, user_agent) 
                        VALUES ('{$admin['id']}', 'LOGIN', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}')";
            mysqli_query($conn, $log_sql);
            
            closeConnection($conn);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Admin account not found or inactive!";
    }
    
    closeConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BagCom</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
        }
        .login-card {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-radius: 15px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .brand-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card">
            <div class="card-header login-header text-center py-4">
                <div class="brand-logo">
                    <i class="fas fa-lock text-primary fa-2x"></i>
                </div>
                <h3 class="mb-0">BagCom Admin</h3>
                <p class="mb-0 opacity-75">Secure Admin Portal</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter password" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Default credentials: admin / admin123
                        </small>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <a href="../index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Store
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>