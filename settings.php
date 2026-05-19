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
        // Fetch current user data to pre-fill forms and sidebar
        // We now fetch the new notification columns AND tfa_enabled
        $stmt = $conn->prepare("SELECT username, email, theme, 
                                      notify_scan_complete, notify_premium_approval, tfa_enabled 
                               FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['username'] = $user['username'];
        } else {
            $error = "User not found in the database.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    $conn->close(); // Close the connection, it will be re-opened by API calls

    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
    $user_theme = $user['theme'] ?? 'dark'; // Get the user's saved theme
    
    // Get notification preferences & 2FA status
    $notify_scan_complete = $user['notify_scan_complete'] ?? true;
    $notify_premium_approval = $user['notify_premium_approval'] ?? true;
    $tfa_enabled = $user['tfa_enabled'] ?? false;
?>
<!DOCTYPE html>
<!-- 
  This line is now dynamic. It sets the theme based on the user's DB preference.
  The 'data-bs-theme' attribute is what Bootstrap 5.3 uses.
-->
<html lang="en" data-bs-theme="<?php echo $user_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* This style makes the settings page look like a professional app */
        .settings-layout {
            display: flex;
            gap: 2rem;
        }
        .settings-nav {
            flex: 0 0 220px; /* Sidebar width */
        }
        .settings-content {
            flex-grow: 1;
            min-width: 0; /* Fixes flexbox overflow issue */
        }
        .settings-card {
            background-color: var(--primary-surface);
            padding: 1.5rem 2rem;
            border-radius: var(--bs-border-radius-lg);
            border: 1px solid var(--bs-border-color);
        }
        /* Style for the sessions list */
        .session-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .session-item:last-child {
            border-bottom: 0;
        }
        .session-icon {
            font-size: 2rem;
            color: var(--bs-secondary);
        }
        .session-details p {
            margin-bottom: 0;
            font-size: 0.9rem;
            color: var(--bs-secondary);
        }
        .session-details strong {
            color: var(--bs-body-color);
        }
        .session-action {
            margin-left: auto;
        }
    </style>
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- 
          SIDEBAR: This is your standard, correct sidebar
        -->
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 mt-2 text-center">
                <a href="index.php"><img src="assets/images/logo.png" alt="AVScanner" style="width: 100%; height: 90px; object-fit: contain; transform: scale(1.4); transform-origin: center center;"></a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link active" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
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
                        <li><a class="dropdown-item active" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logout-btn">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button"><i class="bi bi-list"></i></button>
                <h1 class="h2 fw-bold mb-0">Settings</h1>
            </div>
            
            <!-- This container will be filled by settings.js with success/error messages -->
            <div id="alert-container" class="mb-3">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>

            <!-- NEW PROFESSIONAL LAYOUT -->
            <div class="settings-layout">

                <!-- 1. Settings Navigation Tabs -->
                <div class="settings-nav">
                    <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="v-pills-account-tab" data-bs-toggle="pill" data-bs-target="#v-pills-account" type="button" role="tab" aria-controls="v-pills-account" aria-selected="true">
                            <i class="bi bi-person-fill me-2"></i>Account
                        </button>
                        <button class="nav-link" id="v-pills-app-tab" data-bs-toggle="pill" data-bs-target="#v-pills-app" type="button" role="tab" aria-controls="v-pills-app" aria-selected="false">
                            <i class="bi bi-display-fill me-2"></i>Application
                        </button>
                        <button class="nav-link" id="v-pills-security-tab" data-bs-toggle="pill" data-bs-target="#v-pills-security" type="button" role="tab" aria-controls="v-pills-security" aria-selected="false">
                            <i class="bi bi-shield-lock-fill me-2"></i>Security
                        </button>
                        <button class="nav-link" id="v-pills-billing-tab" data-bs-toggle="pill" data-bs-target="#v-pills-billing" type="button" role="tab" aria-controls="v-pills-billing" aria-selected="false">
                            <i class="bi bi-credit-card-fill me-2"></i>Subscription
                        </button>
                        <button class="nav-link" id="v-pills-data-tab" data-bs-toggle="pill" data-bs-target="#v-pills-data" type="button" role="tab" aria-controls="v-pills-data" aria-selected="false">
                            <i class="bi bi-database-fill-down me-2"></i>Data & Privacy
                        </button>
                    </div>
                </div>

                <!-- 2. Settings Tab Content -->
                <div class="settings-content tab-content" id="v-pills-tabContent">
                    
                    <!-- ACCOUNT TAB (Already built) -->
                    <div class="tab-pane fade show active" id="v-pills-account" role="tabpanel" aria-labelledby="v-pills-account-tab">
                        <div class="settings-card">
                            <h5 class="mb-3">Account Settings</h5>
                            <form id="email-form">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email-current-password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="email-current-password" required placeholder="Enter current password to confirm">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Email</button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">Change Password</h5>
                            <form id="password-form">
                                <div class="mb-3">
                                    <label for="current-password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current-password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new-password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new-password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm-password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- APPLICATION TAB (Already built) -->
                    <div class="tab-pane fade" id="v-pills-app" role="tabpanel" aria-labelledby="v-pills-app-tab">
                        <div class="settings-card">
                            <h5 class="mb-3">Application Settings</h5>
                            <div class="mb-3">
                                <label for="theme-selector" class="form-label">Theme</label>
                                <select id="theme-selector" class="form-select">
                                    <option value="dark" <?php if($user_theme == 'dark') echo 'selected'; ?>>Dark</option>
                                    <option value="light" <?php if($user_theme == 'light') echo 'selected'; ?>>Light</option>
                                </select>
                            </div>
                            
                            <hr class="my-4">

                            <h5 class="mb-3">Notification Settings</h5>
                            <div class="form-check form-switch mb-2">
                              <input class="form-check-input" type="checkbox" role="switch" id="notify-scan-complete" 
                                 <?php if($notify_scan_complete) echo 'checked'; ?>>
                              <label class="form-check-label" for="notify-scan-complete">Email alerts when scan completes</label>
                            </div>
                            <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" role="switch" id="notify-premium-active"
                                 <?php if($notify_premium_approval) echo 'checked'; ?>>
                              <label class="form-check-label" for="notify-premium-active">Email alerts for premium approval</label>
                            </div>
                        </div>
                    </div>

                    <!-- 
                      --- UPDATED SECURITY TAB (With Sessions + 2FA) --- 
                    -->
                    <div class="tab-pane fade" id="v-pills-security" role="tabpanel" aria-labelledby="v-pills-security-tab">
                        
                        <!-- 1. Active Sessions Card -->
                        <div class="settings-card mb-4">
                            <h5 class="mb-3">Active Sessions</h5>
                            <p class="text-secondary">This is a list of all devices that are logged in to your account. Revoke any sessions you do not recognize.</p>
                            
                            <!-- This is where settings.js will load the sessions -->
                            <div id="session-list-container">
                                <div class="text-center p-3">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Loading sessions...
                                </div>
                            </div>

                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Log out of all other devices</h6>
                                    <small class="text-secondary">This will log you out from all other active sessions, but you will remain logged in here.</small>
                                </div>
                                <button class="btn btn-outline-danger" id="logout-all-btn">Log Out All</button>
                            </div>
                        </div>

                        <!-- 2. Two-Factor Authentication (2FA) Card -->
                        <div class="settings-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-2">Two-Factor Authentication (2FA)</h5>
                                    <p class="text-secondary mb-0">Add an extra layer of security to your account by requiring a code from your authenticator app (Google Authenticator, Authy).</p>
                                    
                                    <!-- Status Badge -->
                                    <?php if ($tfa_enabled): ?>
                                        <span class="badge bg-success mt-2">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary mt-2">Disabled</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Button Changes Based on Status -->
                                <?php if ($tfa_enabled): ?>
                                    <button class="btn btn-outline-danger" id="disable-2fa-btn">Disable 2FA</button>
                                <?php else: ?>
                                    <button class="btn btn-primary" id="enable-2fa-btn">Enable 2FA</button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <!-- --- END OF SECURITY TAB --- -->


                    <!-- SUBSCRIPTION TAB (Placeholder) -->
                    <div class="settings-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">Subscription & Billing</h5>
            <p class="text-secondary mb-0">Manage your plan and view usage.</p>
        </div>
        <a href="subscription.php" class="btn btn-primary">Upgrade / Renew Plan</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="p-3 rounded border border-secondary bg-dark">
                <small class="text-muted text-uppercase fw-bold">Current Plan Expiry</small>
                <div class="d-flex align-items-center mt-2">
                    <i class="bi bi-calendar-check text-primary fs-4 me-3"></i>
                    <div>
                        <h5 class="mb-0" id="status-expiry-date">Loading...</h5>
                        <small class="text-success" id="status-badge">Active</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded border border-secondary bg-dark">
                <small class="text-muted text-uppercase fw-bold">Scans Remaining</small>
                <div class="d-flex align-items-center mt-2">
                    <i class="bi bi-activity text-warning fs-4 me-3"></i>
                    <div>
                        <h5 class="mb-0" id="status-scans-left">...</h5>
                        <small class="text-muted">scans available</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-bold mb-3">Payment History</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="payment-history-body">
                <tr><td colspan="5" class="text-center">Loading history...</td></tr>
            </tbody>
        </table>
    </div>
</div>

                    <!-- DATA & PRIVACY TAB (Placeholder) -->
                    <div class="tab-pane fade" id="v-pills-data" role="tabpanel" aria-labelledby="v-pills-data-tab">
                        <div class="settings-card">
                            <h5 class="mb-3">Data & Privacy</h5>
                            <p class="text-secondary">Features to export your scan data or permanently delete your account will be available here soon.</p>
                            <button class="btn btn-outline-secondary" disabled>Export My Data (Coming Soon)</button>
                            <button class="btn btn-danger ms-2" disabled>Delete Account (Coming Soon)</button>
                        </div>
                    </div>

                </div>
                <!-- End Settings Tab Content -->
            </div>
            <!-- End Professional Layout -->
            
        </main>
    </div>
    
    <!-- 
      NEW: 2FA Setup Modal 
      This modal pops up when you click "Enable 2FA"
    -->
    <div class="modal fade" id="tfaModal" tabindex="-1" aria-labelledby="tfaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="tfaModalLabel">Setup Two-Factor Authentication</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <p>1. Scan this QR code with your authenticator app:</p>
            
            <!-- QR Code Image will be injected here by JS -->
            <div id="tfa-qr-container" class="bg-white p-2 d-inline-block rounded mb-3">
                <!-- <img src="..." /> -->
            </div>
            
            <p class="small text-muted">Or enter this secret key manually:<br>
            <code id="tfa-secret-text" class="text-warning fs-5"></code></p>
            
            <hr class="border-secondary my-4">
            
            <p>2. Enter the 6-digit code from your app to verify:</p>
            <input type="text" class="form-control text-center fs-4 w-50 mx-auto mb-3" id="tfa-verify-code" maxlength="6" placeholder="000000">
            
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary w-100" id="verify-2fa-btn">Verify & Enable</button>
          </div>
        </div>
      </div>
    </div>
    <!-- End Modal -->


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- 
      This is your NEW settings JS file.
      We replace the old, simple 'settings.js' with this new one.
    -->
    <script src="assets/js/settings.js"></script> 
</body>
</html>