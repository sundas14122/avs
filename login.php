<?php
    session_start(); // Start the PHP session
    
    // Check if the user is ALREADY logged in
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Automated Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none text-white">
                        <h3 class="fw-bold"><i class="bi bi-shield-check me-2"></i>Automated Vulnerability Scanner</h3>
                    </a>
                    <h1 class="display-4 fw-bold mt-3">Welcome Back</h1>
                </div>

                <form id="login-form">
                    <div class="mb-3">
                        <label for="login-credential" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="login-credential" placeholder="Enter your username or email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
                        </div>
                        <!-- MOVED: Forgot Password Link is now BELOW the input -->
                        <div class="d-flex justify-content-end mt-1">
                            <a href="forgot-password.php" class="text-decoration-none small text-primary">Forgot Password?</a>
                        </div>
                    </div>

                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Login</button></div>
                </form>
                
                <div id="alert-container" class="mt-3"></div>
                
                <div class="text-center mt-4"><p>Don't have an account? <a href="register.php">Register</a></p></div>
                 
                 <footer class="text-center mt-5 text-muted">
                    <small>&copy; 2026 Automated Vulnerability Scanner. All rights reserved.</small><br>
                    <small>
                        <a href="terms.php#privacy" class="text-muted">Privacy Policy</a> &middot; 
                        <a href="terms.php" class="text-muted">Terms of Service</a>
                    </small>
                </footer>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script> 
</body>
</html>