<?php
    session_start(); // Start the PHP session
    
    // We just check if the user is logged in to change the "Back" button
    // We DO NOT redirect them.
    $is_logged_in = isset($_SESSION['user_id']);
    $home_link = $is_logged_in ? 'dashboard.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Policies - Automated Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent pt-4 px-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shield-shaded me-2"></i>
                Automated Vulnerability Scanner
            </a>
            <div class="d-flex">
                <!-- 2. SMART "BACK" BUTTON: Links to dashboard or home -->
                <a class="btn btn-primary" href="<?php echo $home_link; ?>">
                    <?php echo $is_logged_in ? 'Back to Dashboard' : 'Back to Home'; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Legal Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="p-4 p-md-5 rounded" style="background-color: var(--primary-surface);">
                    <h1 class="display-5 fw-bold">Legal Policies</h1>
                    <p class="text-muted">Last updated: 16-November-2025</p>
                    
                    <!-- Quick Navigation -->
                    <ul class="nav nav-pills mb-4">
                        <li class="nav-item"><a href="#privacy" class="nav-link">Privacy Policy</a></li>
                        <li class="nav-item"><a href="#disclaimer" class="nav-link">Disclaimer</a></li>
                        <li class="nav-item"><a href="#refund" class="nav-link">Refund Policy</a></li>
                        <li class="nav-item"><a href="#payment-terms" class="nav-link">Payment Terms</a></li>
                    </ul>

                    <!-- 1. Privacy Policy -->
                    <hr class="my-4">
                    <h2 id="privacy" class="fw-bold">Privacy Policy</h2>
                    <p>This Privacy Policy explains how Automated Vulnerability Scanner (AVS) (“we”, “our”, “us”) collects, uses, and protects your personal information when you use our website and services.</p>
                    
                    <h5>1. Information We Collect</h5>
                    <p>We may collect the following information:</p>
                    <ul>
                        <li><strong>Account Information:</strong> Full name, email address, password (encrypted).</li>
                        <li><strong>Usage Data:</strong> Pages visited, scans performed, logs generated.</li>
                        <li><strong>Payment Information:</strong> Transaction ID, payment method, uploaded screenshot.</li>
                        <li><strong>Technical Info:</strong> IP address, browser type, device information.</li>
                    </ul>
                    <p>We DO NOT store credit/debit card numbers.</p>

                    <h5>2. How We Use Your Information</h5>
                    <p>Your data is used to:</p>
                    <ul>
                        <li>Create and manage user accounts</li>
                        <li>Provide scanning services</li>
                        <li>Verify premium subscription</li>
                        <li>Improve our website performance</li>
                        <li>Maintain security and prevent misuse</li>
                    </ul>
                    <p>We do not sell or rent your personal data to third parties.</p>

                    <h5>3. Cookies</h5>
                    <p>We use cookies for login sessions, user preferences (dark mode, sidebar state), and improving user experience. You can disable cookies in your browser, but the site may not work correctly.</p>

                    <h5>4. Data Security</h5>
                    <p>We use encryption, session protection, and secure storage methods to protect your information. However, no system is 100% secure — users accept this risk.</p>

                    <!-- 2. Disclaimer Page -->
                    <hr class="my-5">
                    <h2 id="disclaimer" class="fw-bold">Disclaimer Page</h2>
                    <p>This website provides a cybersecurity learning and testing tool intended for ethical and legal use only.</p>
                    
                    <h5>1. No Guarantee of Security</h5>
                    <p>The scan results, reports, and findings are provided as-is. We do not guarantee complete detection of all vulnerabilities or the accuracy of results. This tool is not a replacement for professional security audits.</p>

                    <h5>2. Responsibility of Ethical Use</h5>
                    <p>By using this tool, you agree that you will scan only systems you own or have written permission to test. You understand that unauthorized scanning is illegal. We are not responsible for any misuse of this tool.</p>

                    <h5>3. Limitation of Liability</h5>
                    <p>We are not liable for any data loss, system damage, downtime, or legal actions against the user due to misuse.</p>

                    <!-- 3. Refund Policy -->
                    <hr class="my-5">
                    <h2 id="refund" class="fw-bold">Refund Policy (for Premium Plans)</h2>
                    <p>Once a payment is received, verified by the admin, and marked as “Active Subscription”, we cannot issue refunds. This is because digital scanning services are consumed immediately.</p>
                    <p>Refunds may be provided ONLY in rare cases, such as a duplicate payment or a technical error where the service was never activated. Refunds will NOT be given for account suspension due to illegal use. To request a refund, email: billing@avs.com</p>

                    <!-- 4. Manual Payment System Terms -->
                    <hr class="my-5">
                    <h2 id="payment-terms" class="fw-bold">Manual Payment System Terms</h2>
                    
                    <h5>1. Verification Time</h5>
                    <p>To activate Premium, users must submit a Transaction ID (Trx ID) and a screenshot/receipt. Payments are verified manually by the admin. Verification time may take 15 minutes to 24 hours.</p>

                    <h5>2. Incorrect or Fake Payments</h5>
                    <p>We will not activate subscriptions if the screenshot is fake, the Trx ID is invalid, or the amount sent is less than required. Providing fake proof will result in permanent account suspension.</p>

                    <h5>3. No Auto-Renewal</h5>
                    <p>Your subscription will NOT renew automatically. You must manually pay again when your plan expires.</p>

                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="container">
      <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="col-md-4 mb-0 text-muted">&copy; 2026 Automated Vulnerability Scanner</p>
        <!-- 3. UPDATED LINKS: All links point to .php -->
        <a href="index.php" class="col-md-4 d-flex align-items-center justify-content-center mb-3 mb-md-0 me-md-auto link-light text-decoration-none">
          <i class="bi bi-shield-shaded fs-2"></i>
        </a>
        <ul class="nav col-md-4 justify-content-end">
          <li class="nav-item"><a href="login.php" class="nav-link px-2 text-muted">Login</a></li>
          <li class="nav-item"><a href="register.php" class="nav-link px-2 text-muted">Register</a></li>
        </ul>
      </footer>
    </div>

</body>
</html>