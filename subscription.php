<?php
    // 1. Connect to DB and VALIDATE the session
    require_once 'api/db_connect.php';
    
    // validate_session() (from db_connect.php) checks the cookie against the DB
    // and sets $_SESSION['user_id'] if it's valid.
    if (!validate_session($conn)) {
        // If not valid, redirect to login
        header('Location: login.php');
        exit;
    }
    
    // 2. We are now 100% sure the user is authenticated
    $user_id = $_SESSION['user_id'];

    // 3. DATABASE: Fetch the user's current data
    $error = null;
    $user = null;

    try {
        // Fetch current user data to get username and subscription status
        $stmt = $conn->prepare("SELECT username, subscription_status FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Store the username in the session for the sidebar
            $_SESSION['username'] = $user['username'];
        } else {
            $error = "User not found in the database.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    $conn->close();

    // Get username and subscription status
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
    $subscription_status = $user['subscription_status'] ?? 'free'; // Default to 'free' if not set
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <div class="d-flex">
       <!-- 
          4. SIDEBAR: Complete, correct sidebar with .php links
        -->
       <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 mt-2 text-center">
                <a href="index.php"><img src="assets/images/logo.png" alt="AVScanner" style="width: 100%; height: 90px; object-fit: contain; transform: scale(1.4); transform-origin: center center;"></a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link active" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
            </ul>
            <div class="sidebar-footer mt-auto">
                <a class="nav-link" href="help.php"><i class="bi bi-question-circle-fill me-2"></i>Help</a>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <!-- 5. DYNAMIC USERNAME: Shows real username -->
                        <strong id="username-display"><?php echo $username; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <!-- 6. CORRECTED LINKS: All links point to .php -->
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logout-btn">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button"><i class="bi bi-list"></i></button>
                <h1 class="h2 fw-bold mb-0">Subscription Plans</h1>
            </div>

            <!-- This container will be filled by subscription.js with success/error messages -->
            <div id="alert-container">
                <?php if ($error): ?>
                    <!-- Show database errors on page load -->
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
            
            <!-- This box will be updated by subscription.js based on user status -->
            <div class="card mb-4" id="status-box" style="background-color: var(--primary-surface);">
                 <div class="card-body text-center p-4">
                    <h5 class="card-title mb-0">Loading your subscription status...</h5>
                 </div>
            </div>

            <!-- This section will be hidden by subscription.js if user is not "free" -->
           <section id="pricing" class="container py-5 my-5">
        <h2 class="text-center section-header mb-2">Find the Plan That Fits Your Security Needs</h2>
        <p class="text-center text-muted mb-5">Choose a plan to unlock professional vulnerability scanning capabilities.</p>
        
        <div class="row g-4" id="pricing-plans">
            
            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <h3 class="mb-2">Starter Plan</h3>
                    <p class="text-muted small">Perfect for students & beginners.</p>
                    <div class="price">1500 PKR<span class="price-note">/month</span></div>
                    
                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i>Basic Vulnerability Scanning</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Port Scan (Fast)</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Scan History (Limit 10)</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Full Scan Mode</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Advanced Analytics</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No PDF Reports</li>
                    </ul>
                    
                    <button class="btn btn-outline-primary w-100 mt-auto select-plan-btn" 
                            data-plan="starter" 
                            data-price="1500">
                        Choose Starter
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <h3 class="mb-2">Professional Monthly</h3>
                    <p class="text-muted small">Best for developers & testers.</p>
                    <div class="price">4200 PKR<span class="price-note">/month</span></div>
                    
                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i>Unlimited Scans</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i><strong>Full Scan Mode</strong></li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>PDF Report Generation</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Faster Scan Queue</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Priority Support</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Yearly Discount</li>
                    </ul>
                    
                    <button class="btn btn-primary w-100 mt-auto select-plan-btn" 
                            data-plan="pro_monthly" 
                            data-price="4200">
                        Get Professional
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pricing-card popular h-100 position-relative overflow-hidden53">
                    <div class="badge bg-warning text-dark position-absolute top-0 end-0 m-3">Most Popular</div>
                    <h3 class="mb-2">Professional Yearly</h3>
                    <p class="text-muted small">Ideal for organizations.</p>
                    <div class="price">42000 PKR<span class="price-note">/year</span></div>
                    <p class="text-success fw-bold small mb-0">Save 15% compared to monthly</p>

                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i><strong>Unlimited Scans</strong></li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Full Scan Mode</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Real-Time Scan Monitoring</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>All PDF + Detailed Analytics</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Priority Processing & Support</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Early Access to New Features</li>
                    </ul>
                    
                    <button class="btn btn-primary w-100 mt-auto select-plan-btn" 
                            data-plan="pro_yearly" 
                            data-price="42000" 
                            style="box-shadow: 0 0 15px var(--primary-color);">
                        Choose Yearly
                    </button>
                </div>
            </div>

        </div>
        
    </section>
            <!-- This section is hidden by default and shown by subscription.js -->
            <div id="payment-section" class="p-4 mt-5 rounded d-none" style="background-color: var(--primary-surface);">
                <h3 class="mb-4">Complete Your Payment</h3>
                <div class="alert alert-info">
                    You have selected the <strong id="selected-plan-name" class="text-warning"></strong> plan.
                    Please transfer <strong id="selected-plan-price" class="text-warning"></strong> to one of the accounts below.
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="manual-payment-box">
                            <h5 class="text-success">Easypaisa</h5>
                            <p>0300-1234567</p>
                            <small>Sundas/Nimra Admin</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="manual-payment-box">
                            <h5 class="text-danger">JazzCash</h5>
                            <p>0301-7654321</p>
                            <small>Sundas/Nimra Admin</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="manual-payment-box">
                            <h5 class="text-warning">Nayapay</h5>
                            <p>nimra@nypy</p>
                            <small>Sundas/Nimra Admin</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="manual-payment-box">
                            <h5 class="text-info">Sadapay</h5>
                            <p>0302-1122334</p>
                            <small>Sundas/Nimra Admin</small>
                        </div>
                    </div>
                </div>

                <!-- This form will be submitted by subscription.js to api/submit_payment.php -->
                <form id="payment-form" enctype="multipart/form-data">
                    <input type="hidden" id="plan-input" name="plan">
                    <div class="mb-3">
                        <!-- 7. --- FIXED TYPOS: 'class_name' changed to 'class' --- -->
                        <label for="trx-id" class="form-label">Transaction ID (Trx ID)</label>
                        <input type="text" class="form-control" id="trx-id" name="trx_id" placeholder="Enter the Transaction ID from your SMS/App" required>
                    </div>
                    <div class="mb-3">
                        <!-- 7. --- FIXED TYPOS: 'class_name' changed to 'class' --- -->
                        <label for="payment-proof" class="form-label">Upload Payment Proof</label>
                        <input type="file" class="form-control" id="payment-proof" name="payment_proof" accept="image/png, image/jpeg" required>
                        <div class="form-text">Upload a screenshot.</div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">Submit Payment for Verification</button>
                </form>
            </div>

        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <!-- 
      8. PASS STATUS TO JS:
      This passes the PHP status to a JavaScript variable
    -->
    <script>
        const USER_SUBSCRIPTION_STATUS = <?php echo json_encode($subscription_status); ?>;
    </script>
    
    <script src="assets/js/subscription.js?v=<?php echo time(); ?>"></script>
</body>
</html>