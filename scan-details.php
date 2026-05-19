<?php
    session_start(); // Start the PHP session

    // 1. SECURITY: Check if the user is actually logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
    $error = null;
    $scan = null;

    // 2. Get the Scan ID from the URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $error = "No valid scan ID was provided.";
    } else {
        $scan_id = (int)$_GET['id'];

        // 3. DATABASE: Fetch the specific scan FOR THIS USER
        require_once 'api/db_connect.php';
        try {
            // We check for user_id to make sure a user can't see someone else's scan
            // --- UPDATED QUERY: We now JOIN the 'users' table to check subscription_status ---
            $sql = "SELECT s.id, s.target_url, s.scan_type, s.status, s.result_data, s.created_at, u.subscription_status
                    FROM django_scans s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.id = ? AND s.user_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $scan_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $scan = $result->fetch_assoc();
            } else {
                $error = "Scan not found or you do not have permission to view it.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
        $conn->close();
    }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Details - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Add a style for the JSON results box */
        pre {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 5px;
            padding: 15px;
            white-space: pre-wrap; /* Wraps long lines */
            word-wrap: break-word; /* Breaks long words */
            color: #e0e0e0;
            font-family: monospace;
        }
    </style>
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- 
          4. SIDEBAR: All links are .php
        -->
        <nav class="sidebar vh-100 p-3" id="sidebar">
            <div class="sidebar-header mb-4 d-flex align-items-center gap-2">
                <img src="assets/images/logo.jpeg" alt="Logo" width="40" height="40" class="rounded">
                <h5 class="fw-bold mb-0">AVScanner</h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a></li>
                <!-- Mark Scan History as active since we came from there -->
                <li class="nav-item"><a class="nav-link active" href="scan-history.php"><i class="bi bi-search me-2"></i>Scans History</a></li>
                <li class="nav-item"><a class="nav-link" href="new-scan.php"><i class="bi bi-plus-circle-fill me-2"></i>New Scan</a></li>
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
        
        <!-- Main Content -->
        <main class="main-content flex-grow-1 p-4">
            <div class="d-flex align-items-center mb-4">
                <button class="btn btn-dark me-3" id="sidebar-toggle" type="button"><i class="bi bi-list"></i></button>
                <h1 class="h2 fw-bold mb-0">Scan Details</h1>
                
                <!-- 
                  --- THIS BLOCK IS NEW ---
                  It shows "Download" for 'active' users and "Upgrade" for 'free' or 'pending' users.
                -->
                <?php if ($scan && $scan['subscription_status'] == 'active'): ?>
                    <a href="api/generate_pdf.php?id=<?php echo $scan_id; ?>" class="btn btn-success ms-auto">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                    </a>
                <?php elseif ($scan): ?>
                    <a href="subscription.php" class="btn btn-warning ms-auto">
                        <i class="bi bi-star-fill"></i> Upgrade to Download PDF
                    </a>
                <?php endif; ?>
                <!-- END OF NEW BLOCK -->

                <a href="scan-history.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left"></i> Back to History</a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <!-- Show error if scan not found or no ID given -->
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php elseif ($scan): ?>
                        <!-- If scan is found, display its details -->
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <strong>Target:</strong>
                                <p class="fs-5"><?php echo htmlspecialchars($scan['target_url']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong>Scan Type:</strong>
                                <p class="fs-5"><?php echo htmlspecialchars($scan['scan_type']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong>
                                <p class="fs-5">
                                    <?php
                                        $status = htmlspecialchars($scan['status']);
                                        $badge_class = 'bg-secondary'; // Default
                                        if ($status == 'Completed') $badge_class = 'bg-success';
                                        if ($status == 'Failed') $badge_class = 'bg-danger';
                                        if ($status == 'Running') $badge_class = 'bg-info text-dark';
                                        if ($status == 'Pending') $badge_class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> fs-6"><?php echo $status; ?></span>
                                </p>
                            </div>
                        </div>

                        <hr class_name="my-4">

                        <h4 class="mb-3">Raw Scan Results</h4>
                        
                        <pre>
<?php
    // Decode the JSON data from the database and print it
    $result_data = json_decode($scan['result_data']);
    // json_encode with pretty print flags for nice formatting
    echo htmlspecialchars(json_encode($result_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
?>
                        </pre>

                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>