<?php
    session_start(); // Start the PHP session
    
    // 1. --- THIS LOGIC IS NOW CORRECT ---
    // Check if the user is ALREADY logged in
    if (isset($_SESSION['user_id'])) {
        // If they are, send them to the dashboard
        header('Location: dashboard.php');
        exit;
    }
    // If they are NOT logged in, we continue and show them the registration form.
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Automated Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Password Strength Meter Styles */
        .password-requirements {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            padding-left: 0;
            list-style: none;
        }
        .password-requirements li {
            margin-bottom: 0.25rem;
            color: var(--bs-secondary);
            display: flex;
            align-items: center;
        }
        .password-requirements li i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        .password-requirements li.valid {
            color: var(--bs-success);
        }
        .password-requirements li.invalid {
            color: var(--bs-danger);
        }
    </style>
</head>
<body class="login-body">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <!-- 2. --- UPDATED LINKS --- -->
                    <a href="index.php" class="text-decoration-none text-white">
                        <h3 class="fw-bold"><i class="bi bi-shield-check me-2"></i>Automated Vulnerability Scanner</h3>
                    </a>
                    <h1 class="display-5 fw-bold mt-3">Create an Account</h1>
                </div>
                
                <!-- 
                  This form's ID 'register-form' will be used by 'register.js'
                  to send the data to 'api/register.php'
                -->
                <form id="register-form">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input type="text" class="form-control" id="username" placeholder="Choose a username" required></div>
                    </div>
                     <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-envelope"></i></span><input type="email" class="form-control" id="email" placeholder="Enter your email" required></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input type="password" class="form-control" id="password" placeholder="Create a strong password" required></div>
                        
                        <!-- NEW: Password Strength Checklist -->
                        <ul class="password-requirements" id="password-requirements">
                            <li id="rule-length" class="invalid"><i class="bi bi-x-circle-fill"></i> At least 8 characters</li>
                            <li id="rule-uppercase" class="invalid"><i class="bi bi-x-circle-fill"></i> One uppercase letter (A-Z)</li>
                            <li id="rule-lowercase" class="invalid"><i class="bi bi-x-circle-fill"></i> One lowercase letter (a-z)</li>
                            <li id="rule-number" class="invalid"><i class="bi bi-x-circle-fill"></i> One number (0-9)</li>
                            <li id="rule-symbol" class="invalid"><i class="bi bi-x-circle-fill"></i> One special character (!@#$%^&*)</li>
                        </ul>

                    </div>
                     <div class="mb-3">
                        <label for="confirm-password" class="form-label">Confirm Password</label>
                        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock-fill"></i></span><input type="password" class="form-control" id="confirm-password" placeholder="Confirm your password" required></div>
                        <div id="password-match-feedback" class="form-text text-danger d-none">Passwords do not match.</div>
                    </div>
                    
                    <!-- Terms and Conditions Checkbox -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms-agree">
                        <label class="form-check-label" for="terms-agree">
                            I have read and agree to the
                            <!-- 2. --- UPDATED LINKS --- -->
                            <a href="terms.php" target="_blank">Terms & Policies</a>.
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="register-btn" disabled>Register</button>
                    </div>
                </form>
                
                <!-- This container will be filled by register.js with success/error messages -->
                <div id="alert-container" class="mt-3"></div>
                
                <!-- 2. --- UPDATED LINKS --- -->
                <div class="text-center mt-4"><p>Already have an account? <a href="login.php">Login</a></p></div>
                 <footer class="text-center mt-5 text-muted">
                    <small>&copy; 2026 Automated Vulnerability Scanner. All rights reserved.</small><br>
                    <small>
                        <!-- 2. --- UPDATED LINKS --- -->
                        <a href="terms.php#privacy" class="text-muted">Privacy Policy</a> &middot; 
                        <a href="terms.php" class="text-muted">Terms of Service</a>
                    </small>
                </footer>
            </div>
        </div>
    </div>
    <!-- This script contains the fetch() logic to call 'api/register.php' -->
    <script src="assets/js/register.js"></script>
</body>
</html>