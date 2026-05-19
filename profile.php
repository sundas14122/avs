<?php
    // 1. Connect to DB and VALIDATE the session
    require_once 'api/db_connect.php';
    
    // validate_session() (from db_connect.php) checks the cookie against the DB
    // and sets $_SESSION['user_id'] if it's valid.
    if (!validate_session($conn)) {
        header('Location: login.php');
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $error = null;
    $success_message = null;

    // Helper to safely get value from array
    function val($key, $data) {
        return isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
    }

    // --- HANDLE FORM SUBMISSION ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $display_name = $_POST['display_name'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $company = $_POST['company'] ?? '';
        $role = $_POST['role'] ?? '';
        $experience_level = $_POST['experience_level'] ?? 'Beginner';
        $linkedin_url = $_POST['linkedin_url'] ?? '';
        $scan_mode = $_POST['scan_mode'] ?? 'Fast';
        $report_format = $_POST['report_format'] ?? 'PDF';
        $show_name_report = isset($_POST['show_name_report']) ? 1 : 0;

        // Handle Profile Photo Upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
             $target_dir = "uploads/avatars/";
             if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
             
             $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
             $new_filename = "avatar_" . $user_id . "_" . time() . "." . $file_extension;
             $target_file = $target_dir . $new_filename;
             
             if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                 if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                     // Save photo path to DB
                     $stmt_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                     $stmt_photo->bind_param("si", $target_file, $user_id);
                     $stmt_photo->execute();
                     $stmt_photo->close();
                 } else {
                     $error = "Failed to upload photo.";
                 }
             } else {
                 $error = "Invalid file type. Only JPG/PNG allowed.";
             }
        }

        if (!$error) {
            try {
                // Update User Details
                // We use 'username' as the fallback for 'name' logic if needed, but we update specific fields here
                $sql = "UPDATE users SET 
                        display_name = ?, 
                        bio = ?, 
                        company = ?, 
                        role = ?, 
                        experience_level = ?, 
                        linkedin_url = ?, 
                        default_scan_mode = ?, 
                        report_format = ?, 
                        show_name_in_report = ? 
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssii", $display_name, $bio, $company, $role, $experience_level, $linkedin_url, $scan_mode, $report_format, $show_name_report, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                } else {
                    $error = "Failed to update. Error: " . $conn->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- FETCH USER DATA ---
    $user = null;
    try {
        // FIX: Removed 'name' column if it doesn't exist, added 'username'
        // Added all the extra columns we need
        $sql = "SELECT username, email, bio, profile_photo, 
                       display_name, company, role, experience_level, linkedin_url, 
                       default_scan_mode, report_format, show_name_in_report 
                FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Update session username for sidebar
        if ($user) {
            $_SESSION['username'] = $user['username'];
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
    $conn->close();

    // Sidebar username
    $username_display = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-header {
            background-color: var(--primary-surface);
            padding: 2rem;
            border-radius: var(--bs-border-radius-lg);
            border: 1px solid var(--bs-border-color);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--bs-primary);
        }
        .form-section-title {
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            margin-top: 2rem;
            color: var(--bs-secondary);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            font-weight: 600;
        }
    </style>
</head>
<body class="app-body">
    <div class="d-flex">
        <!-- SIDEBAR -->
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
                <a class="nav-link" href="help.php"><i class="bi bi-question-circle-fill me-2"></i>Help</a>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40?u=user" alt="" width="32" height="32" class="rounded-circle me-2">
                        <strong id="username-display"><?php echo $username_display; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="user-dropdown">
                        <li><a class="dropdown-item active" href="profile.php">Profile</a></li>
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
                <h1 class="h2 fw-bold mb-0">Profile Overview</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                
                <div class="profile-header">
                    <div class="position-relative">
                        <?php 
                            // FIX: Default avatar is now a generic male icon if none uploaded
                            $avatar_url = !empty($user['profile_photo']) ? $user['profile_photo'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                        ?>
                        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Profile Photo" class="profile-avatar">
                        <label for="profile_photo" class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                            <i class="bi bi-camera-fill" style="font-size: 0.8rem;"></i>
                        </label>
                        <input type="file" id="profile_photo" name="profile_photo" class="d-none" accept="image/jpeg, image/png">
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1"><?php echo val('display_name', $user) ?: val('username', $user); ?></h3>
                        <p class="text-secondary mb-0"><?php echo val('email', $user); ?></p>
                        
                        <!-- FIX: Logic to remove floating "at" if fields are empty -->
                        <p class="text-secondary small mb-0">
                            <?php 
                                $r = val('role', $user);
                                $c = val('company', $user);
                                if($r && $c) { echo "$r at $c"; }
                                elseif($r) { echo $r; }
                                elseif($c) { echo $c; }
                                else { echo "Security Enthusiast"; }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded" style="background-color: var(--primary-surface);">
                    <p class="text-muted">Update your personal and professional information.</p>

                    <!-- Personal Details -->
                    <div class="form-section-title">Personal Details</div>
                    <div class="row g-3">
                        <!-- Removed 'Full Name' if it maps to 'name' which doesn't exist, using Display Name instead -->
                        <div class="col-md-6">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo val('display_name', $user); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="bio" class="form-label">Bio / Job Title</label>
                            <input type="text" class="form-control" id="bio" name="bio" value="<?php echo val('bio', $user); ?>">
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="form-section-title">Professional Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="company" class="form-label">Organization / Company</label>
                            <input type="text" class="form-control" id="company" name="company" value="<?php echo val('company', $user); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role (Security Position)</label>
                            <input type="text" class="form-control" id="role" name="role" value="<?php echo val('role', $user); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="experience_level" class="form-label">Experience Level</label>
                            <select class="form-select" id="experience_level" name="experience_level">
                                <?php 
                                    $levels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
                                    foreach ($levels as $lvl) {
                                        $selected = ($user['experience_level'] ?? '') === $lvl ? 'selected' : '';
                                        echo "<option value='$lvl' $selected>$lvl</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="linkedin_url" class="form-label">LinkedIn / GitHub / Portfolio URL</label>
                            <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" placeholder="https://..." value="<?php echo val('linkedin_url', $user); ?>">
                        </div>
                    </div>

                    <!-- Profile Preferences -->
                    <div class="form-section-title">Profile Preferences</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="scan_mode" class="form-label">Default Scan Mode</label>
                            <select class="form-select" id="scan_mode" name="scan_mode">
                                <?php 
                                    $modes = ['Fast', 'Deep', 'Full'];
                                    foreach ($modes as $mode) {
                                        $selected = ($user['default_scan_mode'] ?? '') === $mode ? 'selected' : '';
                                        echo "<option value='$mode' $selected>$mode</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="report_format" class="form-label">Preferred Report Format</label>
                            <select class="form-select" id="report_format" name="report_format">
                                <?php 
                                    $formats = ['PDF', 'HTML', 'JSON'];
                                    foreach ($formats as $fmt) {
                                        $selected = ($user['report_format'] ?? '') === $fmt ? 'selected' : '';
                                        echo "<option value='$fmt' $selected>$fmt</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="show_name_report" name="show_name_report" <?php echo ($user['show_name_in_report'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_name_report">Show Name in PDF Reports?</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2">Save Changes</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>