# Hostinger Deployment Plan
## Automated Vulnerability Scanner - Final Year Project

### Project Overview
This is a **PHP-based web application** with a **Django backend** for vulnerability scanning. Both components must be deployed.

---

## Critical Architecture Discovery

```
┌─────────────────┐     ┌─────────────────────┐
│  PHP Frontend   │────▶│  Django Scan Engine │
│  (Hostinger)    │     │  (127.0.0.1:8000)   │
└─────────────────┘     └─────────────────────┘
        │                        │
        ▼                        ▼
    MySQL DB              Python Dependencies
```

**Important**: The PHP app connects to a Django scan engine at `http://127.0.0.1:8000`. For deployment, you need:
- Either deploy **both** on the same server
- Or use a **VPS** (not shared hosting) to run Django

---

## Server Requirements

### Option A: Shared Hosting (Premium Plan - $4.99/mo)
**Limitation**: Cannot run Django scan engine

| Requirement | Status |
|-------------|--------|
| PHP 7.4+ | ✅ Supported |
| MySQL 5.7+ | ✅ Supported |
| mysqli extension | ✅ Supported |
| curl extension | ✅ Supported |
| Composer | ⚠️ Limited |
| Django/Python | ❌ Not supported |

**Result**: PHP frontend works, but **scanning won't function**

### Option B: VPS Hosting (Required for Full Functionality)

| Plan | Price | RAM | Storage | Can Run |
|------|-------|-----|---------|---------|
| VPS 1 | $4.99/mo | 1GB | 25GB | Python + Django |
| VPS 2 | $6.99/mo | 2GB | 50GB | Full Scanner |
| VPS 4 | $11.99/mo | 4GB | 80GB | Production |

---

## Recommended Deployment Configuration

### For Final Year Project (3 months): VPS 2 at $6.99/mo

```
┌─────────────────────────────────────┐
│           VPS (2GB RAM)             │
├─────────────────────────────────────┤
│  • Ubuntu 20.04 LTS                 │
│  • Apache2 / Nginx                   │
│  • PHP 8.1                           │
│  • MySQL 8.0                         │
│  • Python 3.9+                      │
│  • Django 4.x                        │
│  • Composer                          │
└─────────────────────────────────────┘
```

---

## Deployment Checklist

### 1. Server Setup
- [ ] Purchase Hostinger VPS (VPS 2 - $6.99/mo)
- [ ] Set up Ubuntu 20.04 LTS
- [ ] Configure SSH access
- [ ] Set up firewall (ufw)

### 2. LAMP Stack Installation
- [ ] Install Apache2
- [ ] Install PHP 8.1 + extensions (mysqli, curl, mbstring, gd)
- [ ] Install MySQL 8.0
- [ ] Install Composer

### 3. Django Scan Engine Setup
- [ ] Install Python 3.9+
- [ ] Create virtual environment
- [ ] Install Django and dependencies
- [ ] Configure Django to run on port 8000
- [ ] Set up Django service (systemd)

### 4. PHP Application Deployment
- [ ] Upload PHP files to /var/www/html/
- [ ] Configure database connection in api/db_connect.php
- [ ] Run composer install for OTP libraries
- [ ] Set proper file permissions

### 5. Configuration Updates
- [ ] Update api/start_scan.php: Change Django URL from `127.0.0.1:8000` to localhost
- [ ] Configure upload directories (uploads/)
- [ ] Set up SSL certificate (Let's Encrypt - free)

### 6. Testing
- [ ] Test user registration/login
- [ ] Test vulnerability scanning functionality
- [ ] Verify PDF report generation
- [ ] Test file uploads

---

## Cost Summary

| Item | 3-Month Cost |
|------|--------------|
| VPS 2 | $20.97 |
| Domain (optional) | ~$5-10 |
| **Total** | **~$26-31** |

---

## Alternative: Demo-Only Version

If running Django is too complex for your project timeline, you can:

1. **Mock the scan engine**: Modify `api/start_scan.php` to return success without calling Django
2. **Demo purposes only**: Show the UI and database functionality

Would you like me to create a modified version that works without the Django backend for demonstration purposes?
