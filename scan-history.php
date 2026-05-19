<?php
    // 1. Connect to DB and VALIDATE the session
    require_once 'api/db_connect.php';
    
    if (!validate_session($conn)) {
        header('Location: login.php');
        exit;
    }
    
    $logged_in_user_id = $_SESSION['user_id'];

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
        $stmt = $conn->prepare("SELECT id, task_id, status FROM django_scans WHERE user_id = ? AND task_id IS NOT NULL AND task_id <> '' AND LOWER(status) IN ('pending','running') ORDER BY created_at DESC LIMIT 25");
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
    
    $scans = []; 
    $error = null;
    $username = 'User'; 

    try {
        // Pull latest statuses/results from Django before querying history rows.
        sync_user_scans_with_django($conn, $logged_in_user_id);

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

        $sql = "SELECT id, target_url, scan_type, status, result_data, created_at 
                FROM django_scans 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $logged_in_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $scans[] = $row;
            }
        }
        $stmt->close();

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
    <title>Scan History - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <div class="d-flex">
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 mt-2 text-center">
                <a href="index.php"><img src="assets/images/logo.png" alt="AVScanner" style="width: 100%; height: 90px; object-fit: contain; transform: scale(1.4); transform-origin: center center;"></a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
                <li class="nav-item"><a class="nav-link" href="subscription.php"><i class="bi bi-credit-card-fill me-2"></i>Subscription</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
            </ul>
            <div class="sidebar-footer mt-auto">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <strong id="username-display"><?php echo $username; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button"><i class="bi bi-list"></i></button>
                <h1 class="h2 fw-bold mb-0">Scan History</h1>
                <a href="new-scan.php" class="btn btn-primary ms-auto">New Scan</a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (empty($scans)): ?>
                        <div class="text-center p-5">
                            <h5 class="text-muted">No scans found.</h5>
                            <a href="new-scan.php" class="btn btn-primary mt-3">Start a New Scan</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Target</th>
                                        <th>Scan Type</th>
                                        <th>Status</th>
                                        <th>Started</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scans as $scan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($scan['id']); ?></td>
                                            <td><?php echo htmlspecialchars($scan['target_url']); ?></td>
                                            <td><?php echo htmlspecialchars($scan['scan_type']); ?></td>
                                            <td>
                                                <?php
                                                    $status = htmlspecialchars($scan['status']);
                                                    $badge_class = 'bg-secondary';
                                                    if ($status == 'Completed') $badge_class = 'bg-success';
                                                    if ($status == 'Failed') $badge_class = 'bg-danger';
                                                    if ($status == 'Cancelled') $badge_class = 'bg-dark';
                                                    if ($status == 'Running') $badge_class = 'bg-info text-dark';
                                                    if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($scan['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="scan-details.php?id=<?php echo $scan['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye-fill"></i> View
                                                    </a>
                                                    <?php if ($status == 'Running' || $status == 'Pending'): ?>
                                                        <button onclick="cancelScan(<?php echo $scan['id']; ?>)" 
                                                                id="btn-cancel-<?php echo $scan['id']; ?>"
                                                                class="btn btn-sm btn-outline-danger ms-1">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS Function to call Django Backend Cancellation API
        function cancelScan(scanId) {
            if (!confirm("Are you sure you want to stop this scan?")) return;

            const btn = document.getElementById(`btn-cancel-${scanId}`);
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(`api/cancel_scan.php?scan_id=${scanId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error: " + data.error);
                    location.reload();
                }
            })
            .catch(err => {
                console.error(err);
                alert("Server connection failed.");
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle"></i> Cancel';
            });
        }
    </script>
</body>
</html>