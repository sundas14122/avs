<?php
    session_start();
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Vulnerability Scanner</title>
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
                    <h2 class="fw-bold mt-3">Reset Password</h2>
                    <p class="text-muted">Enter your email to receive a reset link.</p>
                </div>

                <form id="forgot-form">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" placeholder="name@example.com" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="reset-btn">Send Reset Link</button>
                    </div>
                </form>
                
                <div id="alert-container" class="mt-3"></div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgot-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const btn = document.getElementById('reset-btn');
            const alertBox = document.getElementById('alert-container');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
            alertBox.innerHTML = '';

            try {
                const response = await fetch('api/send_reset_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email })
                });
                const data = await response.json();

                if (data.success) {
                    alertBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    // If a debug link was sent (because no email server), log it to console
                    if (data.debug_link) {
                        console.log("Reset Link (Debug):", data.debug_link);
                        alert("Check the browser console (F12) for the reset link!");
                    }
                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                }
            } catch (err) {
                alertBox.innerHTML = `<div class="alert alert-danger">Network error.</div>`;
            }
            btn.disabled = false;
            btn.innerHTML = 'Send Reset Link';
        });
    </script>
</body>
</html>