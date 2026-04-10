<?php
    // Prevent stale cached JS from breaking scan API routing during testing.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    // 1. Connect to DB and VALIDATE the session
    require_once 'api/db_connect.php';
    
    // validate_session() checks the cookie against the DB
    if (!validate_session($conn)) {
        header('Location: login.php');
        exit;
    }
    
    // 2. Get the User ID (Required for the scan)
    $logged_in_user_id = $_SESSION['user_id'];
    
    // --- Get Username for Sidebar ---
    $error = null;
    $username = 'User'; 

    try {
        if (isset($_SESSION['username'])) {
            $username = htmlspecialchars($_SESSION['username']);
        } else {
            $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $logged_in_user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($row = $result_user->fetch_assoc()) {
                $username = htmlspecialchars($row['username']);
                $_SESSION['username'] = $username;
            }
            $stmt_user->close();
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Scan - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Custom CSS for this page specifically -->
    <style>
        /* Instruction Card Styling */
        .instruction-card {
            background-color: rgba(33, 37, 41, 0.6); /* Semi-transparent dark */
            border-left: 4px solid #0d6efd; /* Blue accent bar */
            border-radius: 8px;
        }
        .table-custom th {
            font-weight: 600;
            color: #adb5bd;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-custom td {
            font-size: 0.9rem;
            vertical-align: middle;
        }
        /* Risk Level Colors in Text */
        .text-risk-critical { color: #ff4d4d; } /* Red */
        .text-risk-high { color: #ffc107; }     /* Yellow/Orange */
        .text-risk-info { color: #0dcaf0; }     /* Cyan */
        .text-risk-safe { color: #198754; }     /* Green */
    </style>
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- SIDEBAR -->
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4">
                <h4 class="fw-bold"><i class="bi bi-shield-fill me-2"></i>Vulnerability Scanner</h4>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-light w-50" onclick="history.back()" title="Go Back"><i class="bi bi-arrow-left"></i></button>
                    <button class="btn btn-sm btn-outline-light w-50" onclick="history.forward()" title="Go Forward"><i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link active" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
            </ul>
            <div class="sidebar-footer mt-auto">
                <a class="nav-link" href="help.php"><i class="bi bi-question-circle-fill me-2"></i>Help</a>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <strong id="username-display"><?php echo $username; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logout-btn">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button"><i class="bi bi-list"></i></button>
                <h1 class="h2 fw-bold mb-0">Configure New Scan</h1>
            </div>
            
            <!-- ALERT AREA -->
            <div id="scan-alert-container" class="mb-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>

            <div class="p-4 rounded" style="background-color: var(--primary-surface);" id="new-scan-container">
                
                <form id="new-scan-form">
                    
                    <!-- HIDDEN USER ID -->
                    <input type="hidden" id="user_id" value="<?php echo $logged_in_user_id; ?>">

                    <!-- TARGET INPUT -->
                    <div class="mb-3">
                        <label for="target-url" class="form-label">Target</label>
                        <input type="text" class="form-control form-control-lg" id="target-url" name="target_url" placeholder="Enter URL (https://example.com) or IP (45.33.32.156)" required>
                        <div class="form-text text-muted ps-1">
                            <i class="bi bi-arrow-return-right"></i> Pro Tip: Use <code>http://testphp.vulnweb.com/</code> for a safe, full demonstration.
                        </div>
                    </div>

                    <!-- CONFIGURATION ROW -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="scan-type" class="form-label">Scan Type</label>
                            <select class="form-select" id="scan-type" name="scan_type">
                                <option value="full" selected>Full Infrastructure & Web Audit (Recommended)</option>
                                <option value="nmap">Network Discovery (Nmap)</option>
                                <option value="SQLi">SQL Injection Test</option>
                                <option value="nikto">Server Misconfiguration (Nikto)</option>
                                <option value="XSS">Cross-Site Scripting (XSS)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="run-mode" class="form-label">Run Mode</label>
                            <select class="form-select" id="run-mode" name="run_mode">
                                <option value="immediate">Immediate Execution</option>
                                <option value="scheduled" disabled>Scheduled (Premium Feature)</option>
                            </select>
                        </div>
                    </div>

                    <!-- ⭐ SCAN INSTRUCTION & GUIDELINES TABLE ⭐ -->
                    <div class="instruction-card mb-4 p-3">
                        <h5 class="fw-bold mb-3 text-light"><i class="bi bi-info-square-fill me-2 text-primary"></i>Scan Reference Guide</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 20%;">Scan Module</th>
                                        <th scope="col" style="width: 20%;">Required Input</th>
                                        <th scope="col" style="width: 15%;">Avg. Time</th>
                                        <th scope="col">Description & Purpose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-risk-info"><i class="bi bi-layers-fill me-2"></i>Full Audit</td>
                                        <td>URL (Preferred)</td>
                                        <td>2 - 5 Min</td>
                                        <td class="text-muted">Runs all modules sequentially. Best for complete security profiling.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-risk-critical"><i class="bi bi-database-fill-lock me-2"></i>SQL Injection</td>
                                        <td>Full URL</td>
                                        <td>45 - 90 Sec</td>
                                        <td class="text-muted">Tests input forms for database vulnerabilities. Needs <code>http://...</code></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-risk-high"><i class="bi bi-code-slash me-2"></i>XSS Scan</td>
                                        <td>Full URL</td>
                                        <td>10 - 30 Sec</td>
                                        <td class="text-muted">Checks for script injection risks in URL parameters.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-risk-safe"><i class="bi bi-hdd-rack-fill me-2"></i>Server Config</td>
                                        <td>Full URL</td>
                                        <td>30 - 60 Sec</td>
                                        <td class="text-muted">Scans for missing security headers and exposed admin files.</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-white"><i class="bi bi-diagram-3-fill me-2"></i>Network (Nmap)</td>
                                        <td>IP or Domain</td>
                                        <td>15 - 45 Sec</td>
                                        <td class="text-muted">Scans Top 1200 Ports. Can accept IP (e.g. <code>45.33.32.156</code>).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 pt-3 border-top border-secondary">
                            <div class="d-flex">
                                <div class="me-3 text-warning fs-4"><i class="bi bi-shield-exclamation"></i></div>
                                <div>
                                    <small class="text-light d-block fw-bold">Legal Disclaimer</small>
                                    <small class="text-muted" style="font-size: 0.8rem;">
                                        Authorized Use Only. By starting a scan, you certify that you own this target or have explicit written permission to test it.
                                        Unauthorized scanning allows prosecution under the Computer Fraud and Abuse Act.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ⭐ END INSTRUCTION BLOCK ⭐ -->

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="auth-check" required>
                        <label class="form-check-label" for="auth-check">I confirm I have legal authorization to scan this target.</label>
                    </div>
                    
                    <button type="submit" id="start-scan-btn" class="btn btn-primary px-4 py-2 fw-bold">
                        <i class="bi bi-radar me-2"></i>INITIATE SCAN
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/new-scan.js?v=<?php echo filemtime(__DIR__ . '/assets/js/new-scan.js'); ?>"></script>

</body>
</html>