# Deploy Django Backend to Render (Free)

## Step 1: Sign Up for Render

1. Go to: **https://render.com**
2. Click **"Sign Up"**
3. Sign up with GitHub (recommended) or email
4. Verify your email

---

## Step 2: Create GitHub Repository

1. Go to: **https://github.com**
2. Click **"+"** → **"New repository"**
3. Name it: `django-scanner-backend`
4. Make it **Public**
5. Click **"Create repository"**

## Step 3: Upload Django Files to GitHub

### Option A: Using GitHub Website

1. Go to your new repository
2. Click **"uploading an existing file"**
3. Upload all files from the `django_backend` folder:
   - `requirements.txt`
   - `runtime.txt`
   - `manage.py`
   - `Procfile`
   - `scanner_backend/` (entire folder)
   - `scanner/` (entire folder)

### Option B: Using Git Commands

```bash
# Open terminal in django_backend folder
cd django_backend

# Initialize git
git init
git add .
git commit -m "Initial Django backend"

# Add your repository
git remote add origin https://github.com/YOUR_USERNAME/django-scanner-backend.git

# Push to GitHub
git push -u origin main
```

---

## Step 4: Deploy to Render

1. Go to: **https://dashboard.render.com**
2. Click **"New"** → **"Web Service"**
3. Click **"Configure"** next to GitHub
4. Select your repository: `django-scanner-backend`
5. Fill in the details:

| Field | Value |
|-------|-------|
| **Name** | scanner-backend |
| **Branch** | main |
| **Runtime** | Python |
| **Build Command** | `pip install -r requirements.txt` |
| **Start Command** | `gunicorn scanner_backend.wsgi --log-file -` |

6. Click **"Advanced"**
7. Add these Environment Variables:

| Variable | Value |
|----------|-------|
| `PYTHON_VERSION` | `3.11.0` (or 3.9+) |
| `DJANGO_SECRET_KEY` | `your-secret-key-here` |
| `DEBUG` | `False` |

8. Click **"Create Web Service"**

---

## Step 5: Wait for Deployment

1. You'll see build logs running
2. Wait for "Deployed" message (may take 2-5 minutes)
3. Once deployed, you'll get a URL like:
   ```
   https://scanner-backend.onrender.com
   ```

---

## Step 6: Update Your PHP Website

1. After deployment, copy your Render URL (e.g., `scanner-backend.onrender.com`)
2. Go to **InfinityFree File Manager**
3. Navigate to `htdocs` → `api`
4. Edit `start_scan.php`
5. Change this line:
   ```php
   // FROM:
   $django_url = 'http://127.0.0.1:8000/api/scan/start/';
   
   // TO:
   $django_url = 'https://scanner-backend.onrender.com/api/scan/start/';
   ```
6. Save the file

---

## Step 7: Test Your Website

1. Go to your website: `AutomatedVulnerabilityScanner.free.nf`
2. Login and try to start a scan
3. The scan should now work!

---

## Important Notes

### About Render Free Tier:
- **Free for 750 hours/month**
- Service goes to sleep after 15 minutes of inactivity
- First request after sleep takes ~30 seconds to wake up
- Perfect for demos and testing!

### Scanning Features:
The Django backend provides:
- HTTP security header analysis
- SSL/TLS certificate checking
- Common port scanning
- Vulnerability categorization (Critical, High, Medium, Low)

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Build failed | Check requirements.txt has correct versions |
| 500 Error | Check Render logs for error details |
| Scan not working | Make sure you updated start_scan.php with the correct URL |
| Service sleeping | Normal on free tier - wake it up by visiting the URL |

---

## Your Final Setup:

```
PHP Website (InfinityFree)           Django Backend (Render)
https://your-site.free.nf      →    https://scanner-backend.onrender.com
         ↓                                    ↓
    api/start_scan.php ──────────────────────▶ /api/scan/start/
```
