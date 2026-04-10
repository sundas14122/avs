<?php
    // Start the PHP session to check if the user is logged in
    session_start();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automated Vulnerability Scanner for Modern Web Apps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Use the existing style.css but add custom styles for the landing page -->
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        /* Custom styles for the professional landing page */
        body {
            background-color: var(--bs-dark);
            color: var(--bs-light);
        }
        .navbar {
            background-color: var(--bs-dark) !important;
        }
        .hero-section {
            background: radial-gradient(circle, #2a2a2a, var(--bs-dark));
            padding: 6rem 0;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
        }
        .hero-section .lead {
            font-size: 1.25rem;
            color: var(--bs-secondary-bg-subtle);
        }
        .hero-section .usp {
            font-size: 0.9rem;
            color: var(--bs-secondary);
            margin-top: 1rem;
        }
        .btn-primary {
            background-color: var(--bs-blue);
            border: none;
        }
        .btn-outline-primary {
            border-color: var(--bs-blue);
            color: var(--bs-blue);
        }
        .btn-outline-primary:hover {
            background-color: var(--bs-blue);
            color: var(--bs-white);
        }
        .section-header {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
        }
        .feature-card {
            background-color: var(--primary-surface);
            padding: 2rem;
            border-radius: var(--bs-border-radius-lg);
            border: 1px solid var(--bs-border-color);
            height: 100%;
        }
        .feature-icon {
            font-size: 2rem;
            color: var(--bs-blue);
            margin-bottom: 1rem;
        }
        .how-it-works-step {
            padding: 2rem;
            border-left: 3px solid var(--bs-blue);
            background-color: var(--primary-surface);
            border-radius: var(--bs-border-radius);
        }
        .how-it-works-step h4 {
            color: var(--bs-blue);
        }
        .ui-preview img {
            /* Existing formal styling */
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: block; 
            width: 100%; /* Ensures responsiveness */
        }
        .pricing-card {
            background-color: var(--primary-surface);
            padding: 2.5rem 2rem;
            border-radius: var(--bs-border-radius-lg);
            border: 1px solid var(--bs-border-color);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .pricing-card.popular {
            border-color: var(--bs-blue);
            box-shadow: 0 0 20px rgba(var(--bs-blue-rgb), 0.25);
        }
        .pricing-card h3 {
            font-weight: 700;
        }
        .pricing-card .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        .pricing-card .price-note {
            font-size: 1rem;
            font-weight: 400;
            color: var(--bs-secondary);
        }
        .pricing-card .features-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
            flex-grow: 1;
        }
        .pricing-card .features-list li {
            margin-bottom: 0.75rem;
        }
        .pricing-card .features-list i {
            color: var(--bs-success);
            margin-right: 0.5rem;
        }
        .testimonial-card {
            background-color: var(--primary-surface);
            padding: 2rem;
            border-radius: var(--bs-border-radius-lg);
            border: 1px solid var(--bs-border-color);
        }
        .disclaimer-box {
            background-color: var(--primary-surface);
            border: 1px solid var(--bs-warning);
            border-radius: var(--bs-border-radius);
            padding: 1.5rem;
        }
        .footer {
            border-top: 1px solid var(--bs-border-color);
        }
    </style>
</head>
<body class="login-body">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark pt-4 px-4">
        <div class="container-fluid">
            <!-- ⭐ LOGO FIXED HERE ⭐ -->
            <!-- Removed padding/margin classes that pushed it to the corner -->
            <!-- Added 'd-flex align-items-center' for perfect vertical centering with text -->
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <img src="assets/images/index.png" alt="AVS Logo" width="40" height="40" class="d-inline-block align-text-top">
                <span>Automated Vulnerability Scanner</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center"> <!-- Added align-items-center -->
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- If user IS logged in, show Dashboard button -->
                        <li class="nav-item ms-lg-3"><a class="btn btn-primary" href="dashboard.php">Go to Dashboard</a></li>
                    <?php else: ?>
                        <!-- If user is NOT logged in, show Login/Register -->
                        <li class="nav-item ms-lg-3"><a class="nav-link text-white fw-bold" href="login.php">LOGIN</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-primary px-4" href="register.php">GET STARTED</a></li>
                    <?php endif; ?>
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- 1. Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4">Automated Vulnerability Scanner for Modern Web Apps</h1>
            <p class="lead col-lg-8 mx-auto">Scan, detect, and secure vulnerabilities in seconds — no setup required.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="register.php" class="btn btn-primary btn-lg px-4 gap-3">Start Free Scan</a>
                <a href="#pricing" class="btn btn-outline-light btn-lg px-4">Get Premium</a>
            </div>
            <p class="usp col-lg-8 mx-auto">Fast, simple, cloud-ready vulnerability scanning built for developers, students, and security teams.</p>
        </div>
    </section>

    <!-- 2. Key Features Overview -->
    <section id="features" class="container py-5 my-5">
        <h2 class="text-center section-header">Core Capabilities</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-broadcast-pin"></i></div>
                    <h5 class="fw-bold">Automated Port Scanning (Nmap)</h5>
                    <p class="text-secondary">Detect open ports, services, and exposed entry points on your server.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-database-exclamation"></i></div>
                    <h5 class="fw-bold">SQL Injection Detection (SQLMap)</h5>
                    <p class="text-secondary">Test database vulnerabilities with automated payloads on forms and URLs.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-code-slash"></i></div>
                    <h5 class="fw-bold">XSS Detection</h5>
                    <p class="text-secondary">Identify reflected and DOM-based Cross-Site Scripting (XSS) across your pages.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-server"></i></div>
                    <h5 class="fw-bold">Server Misconfiguration (Nikto-like)</h5>
                    <p class="text-secondary">Find missing headers, directory listing, sensitive files, and insecure SSL.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-collection-fill"></i></div>
                    <h5 class="fw-bold">Full Scan Mode</h5>
                    <p class="text-secondary">Run all available scan modules (Nmap, SQLi, XSS, Nikto) with a single click.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <h5 class="fw-bold">Real-Time Dashboard</h5>
                    <p class="text-secondary">View live logs, progress updates, analytics, and full reports all in one place.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. How It Works -->
    <section id="how-it-works" class="py-5" style="background-color: var(--primary-surface);">
        <div class="container my-5">
            <h2 class="text-center section-header">Get Your Report in 3 Simple Steps</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="how-it-works-step">
                        <h4 class="fw-bold mb-3">Step 1 — Enter Your Target</h4>
                        <p class="text-secondary">Add your website or server URL in the scanning dashboard.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="how-it-works-step">
                        <h4 class="fw-bold mb-3">Step 2 — Choose Scan Type</h4>
                        <p class="text-secondary">Select Port Scan, SQLi, XSS, Server Scan, or Full Scan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="how-it-works-step">
                        <h4 class="fw-bold mb-3">Step 3 — Get Your Report</h4>
                        <p class="text-secondary">View findings instantly with charts and download as PDF.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. UI Preview Section -->
   <section class="container py-5 my-5 text-center">
        <h2 class="section-header">Professional UI. Clear Results.</h2>
        <p class="lead text-secondary col-lg-8 mx-auto mb-5">
            Our powerful dashboard is designed to be "intuitive for beginners" yet feature-rich for " security professionals ". 
            View your findings, track history, and generate comprehensive reports seamlessly.
        </p>

        <div class="ui-preview mb-4">
            <img src="assets/images/Dashboard.png" 
                 class="img-fluid" 
                 alt="Main Automated Vulnerability Scanner Dashboard Screenshot">
        </div>

        <div class="row g-4 mt-4">
            <div class="col-md-6 ui-preview">
                <img src="assets/images/History.png" 
                     class="img-fluid" 
                     alt="Scan History and Tracking Page Screenshot">
            </div>
            
            <div class="col-md-6 ui-preview">
                <img src="assets/images/pdf.png" 
                     class="img-fluid" 
                     alt="Automated Scanner PDF Report Preview">
            </div>
        </div>
    </section>

    <!-- 3. Why Choose Our Scanner? -->
    <section class="py-5" style="background-color: var(--primary-surface);">
        <div class="container my-5">
            <h2 class="text-center section-header">Why Choose Our Scanner?</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-browser-chrome"></i></div>
                        <h5 class="fw-bold">No Installation Needed</h5>
                        <p class="text-secondary">Fully web-based. Runs directly in your browser with zero setup.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-speedometer2"></i></div>
                        <h5 class="fw-bold">Fast & Automated</h5>
                        <p class="text-secondary">One-click scanning with pre-built, professional scanning modules.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-person-check-fill"></i></div>
                        <h5 class="fw-bold">Beginner Friendly</h5>
                        <p class="text-secondary">Designed for users with zero cybersecurity experience. No coding needed.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <h5 class="fw-bold">Professional Reports</h5>
                        <p class="text-secondary">PDF + JSON + Charts included for every premium scan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                        <h5 class="fw-bold">Safe & Ethical</h5>
                        <p class="text-secondary">Scans are only allowed on authorized websites. We enforce ethical use.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-gift-fill"></i></div>
                        <h5 class="fw-bold">Free Plan Available</h5>
                        <p class="text-secondary">Get one free scan to test the platform before you decide to upgrade.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Subscription Plans -->
    <section id="pricing" class="container py-5 my-5">
        <h2 class="text-center section-header mb-2">Find the Plan That Fits Your Security Needs</h2>
        <p class="text-center text-muted mb-5">Choose a plan to unlock professional vulnerability scanning capabilities.</p>
        
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <h3 class="mb-2">Starter Plan</h3>
                    <p class="text-muted small">Perfect for students & beginners.</p>
                    <div class="price">1500 PKR<span class="price-note">/month</span></div>
                    
                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i>Basic Vulnerability Scanning</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Port Scan (Fast)</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Scan History (Limit 10)</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Full Scan Mode</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Advanced Analytics</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No PDF Reports</li>
                    </ul>
                    
                    <a href="subscription.php?plan=starter" class="btn btn-outline-primary w-100 mt-auto">Choose Starter</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <h3 class="mb-2">Professional Monthly</h3>
                    <p class="text-muted small">Best for developers & testers.</p>
                    <div class="price">4200 PKR<span class="price-note">/month</span></div>
                    
                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i>Unlimited Scans</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i><strong>Full Scan Mode</strong></li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>PDF Report Generation</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Faster Scan Queue</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Priority Support</li>
                        <li class="text-muted opacity-75"><i class="bi bi-x-circle-fill text-danger"></i>No Yearly Discount</li>
                    </ul>
                    
                    <a href="subscription.php?plan=monthly" class="btn btn-primary w-100 mt-auto">Get Professional</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pricing-card popular h-100 position-relative overflow-hidden">
                    <div class="badge bg-warning text-dark position-absolute top-0 end-0 m-3">Most Popular</div>
                    <h3 class="mb-2">Professional Yearly</h3>
                    <p class="text-muted small">Ideal for organizations.</p>
                    <div class="price">42000 PKR<span class="price-note">/year</span></div>
                    <p class="text-success fw-bold small mb-0">Save 15% compared to monthly</p>

                    <ul class="features-list mt-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i><strong>Unlimited Scans</strong></li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Full Scan Mode</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Real-Time Scan Monitoring</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>All PDF + Detailed Analytics</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Priority Processing & Support</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i>Early Access to New Features</li>
                    </ul>
                    
                    <a href="subscription.php?plan=yearly" class="btn btn-primary w-100 mt-auto" style="box-shadow: 0 0 15px var(--primary-color);">Choose Yearly</a>
                </div>
            </div>

        </div>
        
        <div class="text-center mt-5">
            <p class="text-secondary">We support manual payments via <strong>Easypaisa, JazzCash, and Nayapay</strong>. <a href="subscription.php" class="text-primary text-decoration-none">View Payment Details <i class="bi bi-arrow-right"></i></a></p>
        </div>
    </section>
        
    <!-- 8. Testimonials -->
    <section class="py-5" style="background-color: var(--primary-surface);">
        <div class="container my-5">
            <h2 class="text-center section-header">Who is this for?</h2>
            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="testimonial-card">
                        <p class="fs-5">"This was the perfect tool for my Final Year Project. I could test my web app for vulnerabilities and learn hands-on."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px; background-color: rgba(0, 255, 136, 0.1); color: var(--primary-color);">
                                <i class="fas fa-user-graduate fa-lg"></i>
                            </div>
                            <div><h6 class="mb-0">Students</h6><small class="text-muted">Learn web security hands-on.</small></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="testimonial-card">
                        <p class="fs-5">"A simple, one-click tool to run basic security checks on my own apps before deployment. Saves me time."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px; background-color: rgba(0, 255, 136, 0.1); color: var(--primary-color);">
                                <i class="fas fa-code fa-lg"></i>
                            </div>
                            <div><h6 class="mb-0">Developers</h6><small class="text-muted">Test your own apps easily.</small></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="testimonial-card">
                        <p class="fs-5">"Our team uses this to get quick results on non-critical assets, reducing manual work and saving us time."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px; background-color: rgba(0, 255, 136, 0.1); color: var(--primary-color);">
                                <i class="fas fa-building fa-lg"></i>
                            </div>
                            <div><h6 class="mb-0">Small Businesses</h6><small class="text-muted">Reduce manual work and save time.</small></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- 7. Security & Legal -->
    <section class="container py-5 my-5">
        <div class="col-lg-10 mx-auto">
            <div class="disclaimer-box text-center">
                <h4 class="fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Security & Legal Compliance</h4>
                <p class="lead mb-0">This tool is for ethical testing only. You may scan only applications you own or have explicit written permission to test. Unauthorized scanning is illegal and will result in an immediate account ban.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <div class="container footer">
      <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4">
        <p class="col-md-4 mb-0 text-muted">&copy; 2026 Automated Vulnerability Scanner</p>
        <a href="index.php" class="col-md-4 d-flex align-items-center justify-content-center mb-3 mb-md-0 me-md-auto link-light text-decoration-none">
          <!-- ⭐ UPDATED FOOTER LOGO HERE TOO (Consistency) ⭐ -->
          <img src="assets/images/index.png" alt="Logo" width="40" height="40">
        </a>
        <ul class="nav col-md-4 justify-content-end">
          <li class="nav-item"><a href="mailto:support@avs.com" class="nav-link px-2 text-muted">Contact</a></li>
          <li class="nav-item"><a href="terms.php#privacy" class="nav-link px-2 text-muted">Privacy Policy</a></li>
          <li class="nav-item"><a href="terms.php" class="nav-link px-2 text-muted">Terms of Service</a></li>
        </ul>
      </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>