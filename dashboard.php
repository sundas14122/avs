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
    $is_admin = current_user_is_admin($conn);

    // Admin users should use the dedicated admin console, not the end-user dashboard.
    if ($is_admin) {
        header('Location: admin-panel.php');
        exit;
    }

    function normalize_scan_status($status) {
        $s = strtolower((string)$status);
        if ($s === 'completed') return 'Completed';
        if ($s === 'running') return 'Running';
        if ($s === 'failed') return 'Failed';
        if ($s === 'pending') return 'Pending';
        return ucfirst($s);
    }

    function sync_user_scans_with_django($conn, $user_id) {
        $django_base = rtrim(getenv('DJANGO_API_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
        $stmt = $conn->prepare("SELECT id, task_id, status FROM django_scans WHERE user_id = ? AND task_id IS NOT NULL AND task_id <> '' AND LOWER(status) IN ('pending','running') ORDER BY created_at DESC LIMIT 15");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $local_scan_id = (int)$row['id'];
            $task_id = trim((string)$row['task_id']);
            if ($task_id === '') {
                continue;
            }

            $status_url = $django_base . "/scan/status/" . urlencode($task_id) . "/";
            $ch = curl_init($status_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code < 200 || $http_code >= 300 || !$body) {
                continue;
            }

            $api = json_decode($body, true);
            if (!is_array($api) || empty($api['success']) || empty($api['scan'])) {
                continue;
            }

            $remote_status = normalize_scan_status($api['scan']['status'] ?? 'pending');
            $remote_results = isset($api['scan']['results']) ? json_encode($api['scan']['results']) : null;

            $stmt_upd = $conn->prepare("UPDATE django_scans SET status = ?, result_data = COALESCE(?, result_data) WHERE id = ?");
            if ($stmt_upd) {
                $stmt_upd->bind_param("ssi", $remote_status, $remote_results, $local_scan_id);
                $stmt_upd->execute();
                $stmt_upd->close();
            }
        }

        $stmt->close();
    }
    
    // --- The rest of your page's PHP code ---
    
    // Default values
    $username = 'User';
    $total_scans = 0;
    $assets_scanned = 0;
    $last_scan_date = 'N/A';
    $recent_scans = [];
    $error = null;

    try {
        // Keep MySQL scan history in sync with Django engine states.
        sync_user_scans_with_django($conn, $user_id);

        // Get Username
        $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($row = $result_user->fetch_assoc()) {
            $username = htmlspecialchars($row['username']);
            $_SESSION['username'] = $username; // Store it in the session
        }
        $stmt_user->close();

        // Get Stats (Total Scans, Unique Assets, Last Scan Date)
        $sql_stats = "SELECT 
                        COUNT(*) AS total_scans,
                        COUNT(DISTINCT target_url) AS assets_scanned,
                        MAX(created_at) AS last_scan
                      FROM django_scans 
                      WHERE user_id = ?";
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->bind_param("i", $user_id);
        $stmt_stats->execute();
        $result_stats = $stmt_stats->get_result();
        if ($row = $result_stats->fetch_assoc()) {
            $total_scans = $row['total_scans'];
            $assets_scanned = $row['assets_scanned'];
            if ($row['last_scan']) {
                $last_scan_date = date('M d, Y', strtotime($row['last_scan']));
            }
        }
        $stmt_stats->close();

        // Get Recent Scans (Limit 5)
        $sql_recent = "SELECT id, target_url, status, created_at, scan_type 
                       FROM django_scans 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 5";
        $stmt_recent = $conn->prepare($sql_recent);
        $stmt_recent->bind_param("i", $user_id);
        $stmt_recent->execute();
        $result_recent = $stmt_recent->get_result();
        while($row = $result_recent->fetch_assoc()) {
            $recent_scans[] = $row;
        }
        $stmt_recent->close();

    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    $conn->close();
    
    // NOTE: High-Risk Vulns is hard to calculate from JSON. We'll set it to 0 for now.
    $high_risk_vulns = 0; 
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- 
          3. SIDEBAR: All links are now .php
        -->
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 d-flex align-items-center gap-2">
                <img src="assets/images/logo.jpeg" alt="Logo" width="40" height="40" class="rounded">
                <h5 class="fw-bold mb-0">AVScanner</h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
                <?php if ($is_admin): ?>
                <li class="nav-item"><a class="nav-link" href="admin-panel.php"><i class="bi bi-shield-lock-fill me-2"></i>Admin Panel</a></li>
                <?php endif; ?>
            </ul>
            <div class="sidebar-footer mt-auto">
                <a class="nav-link" href="help.php"><i class="bi bi-question-circle-fill me-2"></i>Help</a>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <!-- UPDATED: Shows real username -->
                        <strong id="username-display"><?php echo $username; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <!-- UPDATED: Links to .php -->
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- 
                          THIS IS THE FIX:
                          href="api/logout.php" is changed to href="#" and id="logout-btn"
                        -->
                        <li><a class="dropdown-item" href="#" id="logout-btn">Sign out</a></li>

                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-dark me-3" id="sidebar-toggle" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h2 fw-bold mb-0">Dashboard</h1>
                </div>
                <div>
                    <!-- UPDATED: Quick Scan button links to new-scan.php -->
                    <a href="new-scan.php" class="btn btn-primary" id="quick-scan-btn"><i class="bi bi-lightning-charge-fill me-2"></i>Quick Scan</a>
                    <button class="btn btn-dark"><i class="bi bi-bell"></i></button>
                </div>
            </div>

            <!-- Show database error if it exists -->
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- 
              4. DYNAMIC STAT CARDS: Now filled with PHP data
            -->
            <div class="row g-4 mb-4">
                <div class="col-md-3"><div class="stat-card"><h6 class="text-secondary">Total Scans Performed</h6><h1 id="total-vulns"><?php echo $total_scans; ?></h1></div></div>
                <div class="col-md-3"><div class="stat-card"><h6 class="text-secondary">High-Risk Vulnerabilities</h6><h1 class="high-risk" id="high-risk-vulns"><?php echo $high_risk_vulns; ?></h1></div></div>
                <div class="col-md-3"><div class="stat-card"><h6 class="text-secondary">Assets Scanned</h6><h1 id="assets-scanned"><?php echo $assets_scanned; ?></h1></div></div>
                <div class="col-md-3"><div class="stat-card"><h6 class="text-secondary">Last Scan Date</h6><h1 id="last-scan-date" style="font-size: 1.5rem;"><?php echo $last_scan_date; ?></h1></div></div>
            </div>
            
            <!-- 
              5. UPDATED Action Buttons: Links to .php
            -->
            <div class="mb-4">
                <a href="new-scan.php" class="btn btn-primary">Start New Scan</a>
                <a href="scan-history.php" class="btn btn-secondary">View Scan History</a>
                <a href="subscription.php" class="btn btn-warning">Upgrade Plan</a>
            </div>
            
            <h3 class="h4 fw-bold mb-3">Recent Scan Activities</h3>
            <!-- 
              6. DYNAMIC TABLE: Built with PHP
            -->
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>TARGET</th><th>TYPE</th><th>STATUS</th><th>DATE</th><th>ACTIONS</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_scans)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">
                                    No recent scan activity.
                                    <a href="new-scan.php" class="d-block mt-2">Start your first scan</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_scans as $scan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($scan['target_url']); ?></td>
                                    <td><?php echo htmlspecialchars($scan['scan_type']); ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($scan['status']);
                                            $badge_class = 'bg-secondary'; // Default
                                            if ($status == 'Completed') $badge_class = 'bg-success';
                                            if ($status == 'Failed') $badge_class = 'bg-danger';
                                            if ($status == 'Running') $badge_class = 'bg-info text-dark';
                                            if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($scan['created_at'])); ?></td>
                                    <td>
                                        <a href="scan-details.php?id=<?php echo $scan['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye-fill"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- 
      THIS IS THE FIX:
      The old, broken dashboard.js file is no longer loaded.
      All data is now loaded by PHP.
    -->
    <!-- <script src="assets/js/dashboard.js"></script> -->
    
</body>
</html>