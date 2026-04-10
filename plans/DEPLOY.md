# InfinityFree Deployment Guide
## Automated Vulnerability Scanner

### Step 1: Get InfinityFree Credentials
After signing up, you'll receive:
- **Panel URL**: https://cp.infinityfree.net
- **FTP Host**: `ftpupload.net`
- **MySQL Host**: `sql.infinityfree.com`

---

### Step 2: Create MySQL Database
1. Login to InfinityFree panel
2. Go to **MySQL Databases**
3. Create a new database (e.g., `avs_db`)
4. Create a MySQL user and password
5. Add user to database with ALL privileges

**Save these details:**
- Database Name: `avs_db`
- Database Host: `sql.infinityfree.com`
- Username: `your_mysql_user`
- Password: `your_password`

---

### Step 3: Import Database
1. Go to **phpMyAdmin** in InfinityFree panel
2. Select your database
3. Click **Import** tab
4. Upload `database.sql` file
5. Click **Go**

---

### Step 4: Upload Files
**Option A - File Manager (Easiest):**
1. Go to **File Manager** in InfinityFree
2. Navigate to `htdocs` folder
3. Delete existing files
4. Click **Upload** and upload all PHP files

**Option B - FTP:**
1. Use FTP client (FileZilla)
2. Connect to `ftpupload.net`
3. Upload files to `/htdocs` folder

---

### Step 5: Configure Database Connection
Edit `api/db_connect.php` and update with your InfinityFree credentials:

```php
<?php
// Database credentials - UPDATE THESE
define('DB_SERVER', 'sql.infinityfree.com');  // Change from 'localhost'
define('DB_USERNAME', 'your_mysql_user');     // Your MySQL username
define('DB_PASSWORD', 'your_password');        // Your MySQL password
define('DB_NAME', 'avs_db');                   // Your database name
?>
```

---

### Step 6: Test Your Site
1. Go to your InfinityFree domain
2. Try registering a new user
3. Try logging in

---

### Common Issues

| Error | Solution |
|-------|----------|
| "Database Connection Failed" | Check database credentials in db_connect.php |
| "Access Denied" | Make sure MySQL user has privileges to the database |
| White screen | Check PHP error logs in File Manager |
| File not found | Ensure all files are in htdocs folder |

---

### Your App URL
After deployment, your app will be at:
- `youraccount.infinityfreeapp.com`

(Note: Replace "youraccount" with your actual subdomain from InfinityFree)
