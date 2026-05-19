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
    
    // --- The rest of your page's PHP code ---
    $error = null;
    $username = 'User'; // Default

    // 2. Get Username (Robustly)
    try {
        // Try session first, then database
        if (isset($_SESSION['username'])) {
            $username = htmlspecialchars($_SESSION['username']);
        } else {
            // Not in session, so fetch from DB
            $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($row = $result_user->fetch_assoc()) {
                $username = htmlspecialchars($row['username']);
                $_SESSION['username'] = $username; // Store it for next time
            }
            $stmt_user->close();
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    
    $conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Custom styles for the help page */
        .help-card {
            background-color: var(--primary-surface);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius-lg);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .help-icon {
            font-size: 2rem;
            color: var(--bs-primary);
            margin-bottom: 1rem;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
            border-radius: 20px;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
        }
        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(0,0,0,.125);
        }
    </style>
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- 
          SIDEBAR: Complete, correct sidebar with .php links
        -->
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 text-center">
                <a href="index.php"><img src="assets/images/logo.png" alt="AVScanner" style="max-width: 100%; height: 60px; object-fit: contain;" class="rounded"></a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
            </ul>
            <div class="sidebar-footer mt-auto">
                <!-- Set Help link as active -->
                <a class="nav-link active" href="help.php"><i class="bi bi-question-circle-fill me-2"></i>Help</a>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <!-- 4. DYNAMIC USERNAME: Shows real username -->
                        <strong id="username-display"><?php echo $username; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <!-- 5. CORRECTED LINKS: All links point to .php -->
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logout-btn">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="h2 fw-bold mb-0">Help & Support Center</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Left Column: Guides -->
                <div class="col-lg-8">
                    
                    <!-- 1. Getting Started -->
                    <div class="help-card">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-rocket-takeoff help-icon me-3 mb-0"></i>
                            <h4 class="fw-bold mb-0">1. Getting Started</h4>
                        </div>
                        <p>Follow these simple steps to launch your first security scan:</p>
                        <ol class="list-group list-group-numbered list-group-flush">
                            <li class="list-group-item bg-transparent text-white">Open the <a href="new-scan.php" class="text-primary">New Scan</a> page from the sidebar.</li>
                            <li class="list-group-item bg-transparent text-white">Enter your target <strong>URL</strong> (e.g., <code>http://example.com</code>) or <strong>IP address</strong>.</li>
                            <li class="list-group-item bg-transparent text-white">Select the <strong>Scan Type</strong> (Port Scan, SQL Injection, XSS, or Full Scan).</li>
                            <li class="list-group-item bg-transparent text-white">Click the <strong>Start Scan</strong> button.</li>
                        </ol>
                        <p class="mt-3 text-muted small"><i class="bi bi-info-circle me-1"></i> Your scan will enter the queue and begin automatically.</p>
                    </div>

                    <!-- 2. Scan Statuses -->
                    <div class="help-card">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-activity help-icon me-3 mb-0"></i>
                            <h4 class="fw-bold mb-0">2. Understanding Scan Statuses</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-borderless align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Status</th>
                                        <th scope="col">Meaning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark status-badge">Pending</span></td>
                                        <td>Your scan is added to the queue and will begin shortly.</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info text-dark status-badge">Running</span></td>
                                        <td>The scanner is actively analyzing your target.</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success status-badge">Completed</span></td>
                                        <td>The scan is finished and results are ready to view.</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger status-badge">Failed</span></td>
                                        <td>The scan could not be completed (e.g., unreachable target).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. Troubleshooting (Accordion) -->
                    <div class="help-card">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-tools help-icon me-3 mb-0"></i>
                            <h4 class="fw-bold mb-0">5. Troubleshooting Guide</h4>
                        </div>
                        <div class="accordion accordion-flush" id="troubleshootingAccordion">
                            <div class="accordion-item bg-transparent">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        <strong>Why is my scan stuck on "Pending"?</strong>
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body text-secondary">
                                        This usually happens due to high server load. Your scan is waiting for an available worker. Also, check if your subscription is active. Try refreshing the page after a few minutes.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item bg-transparent">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        <strong>Why did my scan fail?</strong>
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body text-secondary">
                                        Possible reasons include: Invalid URL/IP, Firewall blocking our probes, or the target server is down. Please verify the URL is accessible and try again.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item bg-transparent">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                        <strong>Why can't I download PDF reports?</strong>
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body text-secondary">
                                        PDF exports are a Premium feature. If your plan is Free or Pending, please <a href="subscription.php">upgrade your plan</a> to unlock full reports.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Account & Contact -->
                <div class="col-lg-4">
                    
                    <!-- 4. Subscription Support -->
                    <div class="help-card bg-dark border-secondary">
                        <h5 class="fw-bold mb-3"><i class="bi bi-credit-card-2-front me-2 text-warning"></i>Subscription</h5>
                        <p class="text-muted small">Upgrade to Premium to unlock:</p>
                        <ul class="list-unstyled small text-secondary mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Unlimited Scans</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>PDF Export</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Priority Support</li>
                        </ul>
                        <a href="subscription.php" class="btn btn-warning w-100">Manage Subscription</a>
                    </div>

                    <!-- 6. Legal -->
                    <div class="help-card bg-dark border-secondary">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Legal Usage</h5>
                        <p class="text-secondary small mb-0">
                            This tool is strictly for <strong>ethical testing</strong> of systems you own or have written permission to test. Unauthorized scanning is illegal and will result in an immediate ban.
                        </p>
                        <a href="terms.php" class="btn btn-link btn-sm px-0 text-decoration-none">Read Terms & Conditions &rarr;</a>
                    </div>

                    <!-- 8. Contact -->
                    <div class="help-card">
                        <h5 class="fw-bold mb-3">Need more help?</h5>
                        <p class="text-secondary small">If you encountered a technical issue, contact our support team.</p>
                        <div class="d-grid gap-2">
                            <a href="mailto:support@avs.com" class="btn btn-outline-light">
                                <i class="bi bi-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                        <p class="text-muted text-center mt-3 mb-0" style="font-size: 0.8rem;">Response time: 24–48 hours</p>
                    </div>

                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>