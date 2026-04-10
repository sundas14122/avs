# Complete InfinityFree Deployment Guide
## Step-by-Step for Beginners

---

## Part 1: Create InfinityFree Account

### Step 1.1: Sign Up
1. Open browser and go to: **https://infinityfree.net**
2. Click the big **"Get Free Hosting"** button
3. In the "Choose a subdomain" box, type a name for your website:
   - Example: `vuln-scanner`
   - Your URL will be: `vuln-scanner.infinityfreeapp.com`
4. Scroll down and enter your:
   - Email address
   - Password (create a strong password)
5. Check "I agree to the Terms of Service"
6. Click **"Create Account"**

### Step 1.2: Verify Email
1. Check your email inbox (also check spam folder)
2. Look for email from InfinityFree
3. Click the verification link inside

### Step 1.3: Login to Control Panel
1. Go to: **https://cp.infinityfree.net**
2. Enter your email and password
3. Click **"Log In"**

---

## Part 2: Create Database

### Step 2.1: Create MySQL Database
1. In the left sidebar of control panel, click **"MySQL Databases"**
2. Under "Create a new MySQL database":
   - Database Name: `avs_db` (type this exactly)
   - Click **"Create Database"**

### Step 2.2: Create MySQL User
1. Still on MySQL Databases page, scroll to "Create a new MySQL user"
2. Enter:
   - Username: `admin` (or any name you want)
   - Password: `Pass123456!` (remember this!)
   - Confirm Password: `Pass123456!`
3. Click **"Create User"**

### Step 2.3: Add User to Database
1. Scroll to "Grant a user access to a database"
2. Select:
   - User: `admin` (or your username)
   - Database: `avs_db`
3. Click **"Grant"**
4. Make sure ALL privileges are checked
5. Click **"Grant Privileges"**

**IMPORTANT - Save these details:**
- Database Host: `sql.infinityfree.com`
- Database Name: `avs_db`
- Database User: `admin` (your username)
- Database Password: `Pass123456!` (your password)

---

## Part 3: Import Database Schema

### Step 3.1: Open phpMyAdmin
1. In InfinityFree control panel, click **"phpMyAdmin"** in the sidebar
2. A new tab/window will open

### Step 3.2: Import SQL File
1. In phpMyAdmin, click on your database `avs_db` in the left panel
2. Click the **"Import"** tab at the top
3. Click **"Choose File"**
4. Select the `database.sql` file from your computer (it's in the `plans` folder)
5. Scroll down and click **"Go"** or **"Import"**
6. You should see "Import has been successfully finished"

---

## Part 4: Upload Files

### Step 4.1: Open File Manager
1. Go back to InfinityFree control panel
2. Click **"File Manager"** in the sidebar
3. Double-click on **"htdocs"** folder

### Step 4.2: Delete Existing Files
1. Select all files in htdocs (click first file, hold Shift, click last file)
2. Click **"Delete"** button
3. Confirm deletion

### Step 4.3: Upload Your PHP Files
1. Click **"Upload"** button
2. Click **"Select File"** and select ALL these files from your computer:
   - index.php
   - login.php
   - register.php
   - forgot-password.php
   - reset-password.php
   - dashboard.php
   - new-scan.php
   - scan-history.php
   - scan-details.php
   - profile.php
   - settings.php
   - subscription.php
   - help.php
   - terms.php
   - test.php
3. Wait for all uploads to complete
4. Go back to htdocs folder

### Step 4.4: Upload API Folder
1. In File Manager, click **"New Folder"**
2. Name it: `api`
3. Double-click to enter the api folder
4. Upload all these files from your `api` folder:
   - db_connect.php
   - login.php
   - register.php
   - logout.php
   - logout_session.php
   - get_sessions.php
   - send_reset_link.php
   - start_scan.php
   - generate_pdf.php
   - submit_payment.php
   - tfa_generate.php
   - tfa_enable.php
   - update_account_settings.php
   - update_notifications.php
   - update_theme.php
   - update_user.php
   - composer.json
   - TOTP.php
   - fpdf.php

### Step 4.5: Upload Assets Folder
1. Go back to htdocs
2. Create folder: `assets`
3. Create `assets/css` and upload `style.css`
4. Create `assets/js` and upload all .js files
5. Create `assets/images` and upload all images

### Step 4.6: Upload Uploads Folder
1. Go back to htdocs
2. Create folder: `uploads`
3. Create subfolder: `uploads/avatars`
4. Set permissions to 755 or 777 (right-click folder > Properties)

---

## Part 5: Configure Database Connection

### Step 5.1: Edit db_connect.php
1. In File Manager, go to `api` folder
2. Click on `db_connect.php`
3. Click **"Edit"** button
4. Replace the entire file content with:

```php
<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database credentials - INFINITYFREE SETTINGS
define('DB_SERVER', 'sql.infinityfree.com');
define('DB_USERNAME', 'admin');
define('DB_PASSWORD', 'Pass123456!');
define('DB_NAME', 'avs_db');

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set the character set to utf8
$conn->set_charset("utf8");

// --- SESSION VALIDATION ---
function validate_session($conn) {
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $stmt = $conn->prepare("SELECT user_id FROM user_sessions WHERE session_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $stmt_update = $conn->prepare("UPDATE user_sessions SET last_seen = CURRENT_TIMESTAMP WHERE session_token = ?");
            $stmt_update->bind_param("s", $token);
            $stmt_update->execute();
            
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            return true;
        }
        
        unset($_SESSION['user_id']);
        setcookie('auth_token', '', time() - 3600, '/');
        return false;
    }
    
    unset($_SESSION['user_id']);
    return false;
}
?>
```

5. Click **"Save Changes"**

---

## Part 6: Test Your Website

### Step 6.1: Visit Your Site
1. Open a new browser tab
2. Go to: `https://vuln-scanner.infinityfreeapp.com`
   (replace "vuln-scanner" with your subdomain)

### Step 6.2: Test Registration
1. Click **"Register"** or **"Sign Up"**
2. Enter:
   - Username: `testuser`
   - Email: `test@example.com`
   - Password: `Test123456!`
3. Click **"Register"**

### Step 6.3: Test Login
1. Use the credentials you just created
2. Click **"Login"**
3. You should see the dashboard

---

## Troubleshooting

### Problem: "Database Connection Failed"
**Solution:** Check your db_connect.php credentials are correct

### Problem: "Access Denied for user"
**Solution:** Make sure you granted ALL privileges to your MySQL user

### Problem: White/Blank Page
**Solution:** Check error logs in File Manager > logs folder

### Problem: Files Not Found (404)
**Solution:** Make sure all files are in htdocs folder, not in subfolders

---

## Your Website URL
**https://your-subdomain.infinityfreeapp.com**

Replace "your-subdomain" with what you chose during sign up!
