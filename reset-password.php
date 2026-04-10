<?php
require_once 'api/db_connect.php';

$token = $_GET['token'] ?? '';
$token_hash = hash('sha256', $token);
$user_id = null;
$error = null;
$success = null;

// 1. Verify Token
if (empty($token)) {
    $error = "Invalid link. No token provided.";
} else {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    } else {
        $error = "Link is invalid or has expired.";
    }
    $stmt->close();
}

// 2. Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters.";
    } else {
        // Hash new password
        $new_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Update DB and clear token
        $stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt_update->bind_param("si", $new_hash, $user_id);
        
        if ($stmt_update->execute()) {
            $success = "Password updated successfully! You can now <a href='login.php'>login</a>.";
            // Hide the form
            $user_id = null; 
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Vulnerability Scanner</title>
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
                    <h2 class="fw-bold mt-3">Set New Password</h2>
                </div>

                <div class="p-4 rounded" style="background-color: var(--primary-surface);">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if ($user_id): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Update Password</button>
                            </div>
                        </form>
                    <?php elseif (!$success && !$error): ?>
                         <!-- Fallback -->
                         <div class="alert alert-warning">Please use the link from your email.</div>
                    <?php endif; ?>
                    
                    <?php if (!$user_id && !$success): ?>
                        <div class="text-center mt-3">
                            <a href="forgot-password.php">Request a new link</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>