# DigitalOcean VPS Deployment Runbook (`avscanner.tech`)

This project is now aligned for VPS deployment with PHP + MySQL + Django scan engine.

## 1. Target Architecture

- Public app: `https://avscanner.tech`
- Optional API subdomain for Django: `https://api.avscanner.tech`
- PHP frontend/API served by Nginx + PHP-FPM
- Django scan engine served by Gunicorn behind Nginx
- MySQL on VPS or managed database

## 2. DNS Setup

Create these DNS records at your domain provider:

- `A` record: `@` -> `<VPS_PUBLIC_IP>`
- `A` record: `www` -> `<VPS_PUBLIC_IP>`
- `A` record: `api` -> `<VPS_PUBLIC_IP>` (if using separate API host)

## 3. Server Packages (Ubuntu)

```bash
sudo apt update
sudo apt install -y nginx mysql-server php-fpm php-mysql php-curl php-mbstring php-xml unzip git python3 python3-venv python3-pip certbot python3-certbot-nginx
```

## 4. Deploy Project Files

```bash
sudo mkdir -p /var/www/avscanner
sudo chown -R $USER:$USER /var/www/avscanner
cd /var/www/avscanner
# copy project files here
```

Recommended permissions:

```bash
find /var/www/avscanner -type d -exec chmod 755 {} \;
find /var/www/avscanner -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/avscanner/uploads
```

## 5. MySQL Setup

```sql
CREATE DATABASE avs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'avs_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON avs_db.* TO 'avs_user'@'localhost';
FLUSH PRIVILEGES;
```

Import schema:

- Run `plans/database.sql` in phpMyAdmin or MySQL CLI.

## 6. PHP Environment Variables (Nginx/PHP-FPM)

Set these in your Nginx site config via `fastcgi_param` or in PHP-FPM pool env:

- `DB_SERVER=127.0.0.1`
- `DB_USERNAME=avs_user`
- `DB_PASSWORD=<strong-db-password>`
- `DB_NAME=avs_db`
- `APP_BASE_URL=https://avscanner.tech`
- `DJANGO_API_URL=https://api.avscanner.tech/api/scan/start/`
- `DJANGO_API_BASE_URL=https://api.avscanner.tech/api`
- `ADMIN_EMAILS=admin@avscanner.tech`
- `ADMIN_USER_IDS=41`

## 7. Django Setup

```bash
cd /var/www/avscanner/django_backend
python3 -m venv .venv
source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
```

Set Django environment:

- `DJANGO_SECRET_KEY=<strong-random-secret>`
- `DEBUG=False`
- `ALLOWED_HOSTS=api.avscanner.tech,avscanner.tech`

Run migrations:

```bash
python manage.py migrate
```

Create Gunicorn service `/etc/systemd/system/avscanner-gunicorn.service`:

```ini
[Unit]
Description=AVScanner Django Gunicorn
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/avscanner/django_backend
Environment="DJANGO_SECRET_KEY=<strong-random-secret>"
Environment="DEBUG=False"
Environment="ALLOWED_HOSTS=api.avscanner.tech,avscanner.tech"
ExecStart=/var/www/avscanner/django_backend/.venv/bin/gunicorn scanner_backend.wsgi:application --bind 127.0.0.1:8000 --workers 3 --timeout 120
Restart=always

[Install]
WantedBy=multi-user.target
```

Start service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable avscanner-gunicorn
sudo systemctl start avscanner-gunicorn
sudo systemctl status avscanner-gunicorn
```

## 8. Nginx Virtual Hosts

Create one site for `avscanner.tech` (PHP) and one for `api.avscanner.tech` (proxy to Gunicorn).

PHP site key points:

- `root /var/www/avscanner;`
- `index index.php index.html;`
- route `~ \.php$` to PHP-FPM socket
- deny direct access to sensitive files (`.env`, `.git`, `plans/`)

Django API site key points:

- `server_name api.avscanner.tech;`
- `location / { proxy_pass http://127.0.0.1:8000; ... }`

Then:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 9. SSL Certificates

```bash
sudo certbot --nginx -d avscanner.tech -d www.avscanner.tech
sudo certbot --nginx -d api.avscanner.tech
```

## 10. Post-Deploy Verification

1. Open `https://avscanner.tech/login.php` and log in.
2. Confirm admin account resolves with `admin@avscanner.tech`.
3. Start scan from `new-scan.php`.
4. Confirm `scan-history.php` loads rows and cancel works.
5. Verify 2FA enable and disable both work.
6. Verify password reset link domain is `https://avscanner.tech`.
7. Verify no PHP warnings/errors are displayed to users.

## 11. Security Hardening Checklist

- Keep port `3306` closed publicly unless required.
- Allow only ports `22`, `80`, `443` on firewall.
- Use SSH keys and disable password login for SSH.
- Rotate DB and Django secrets after first deployment.
- Remove or lock down any test/debug pages (`test.php`).
- Keep backups enabled for DB and uploads.

## 12. Known Important Notes

- PHP currently stores UI scan history in MySQL table `django_scans`.
- Django is the scanning engine and receives requests via PHP API.
- Ensure `DJANGO_API_URL` and `DJANGO_API_BASE_URL` are reachable from PHP process.
