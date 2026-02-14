# Deployment Guide - OPF Capital Dashboard

This document provides step-by-step instructions for deploying the OPF Capital Dashboard to a production environment.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Installation Steps](#installation-steps)
4. [Database Setup](#database-setup)
5. [Environment Configuration](#environment-configuration)
6. [Security Hardening](#security-hardening)
7. [Running the Application](#running-the-application)
8. [Post-Deployment Verification](#post-deployment-verification)
9. [Backup & Recovery](#backup--recovery)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before deploying, ensure you have:

- [ ] Production server with SSH access
- [ ] Domain name configured (optional but recommended)
- [ ] SSL certificate for HTTPS (required for production)
- [ ] Database server (MySQL 8.0+ or PostgreSQL 13+)
- [ ] SMTP credentials for email notifications
- [ ] Backup storage location configured

---

## Server Requirements

### Minimum Specifications
- **CPU:** 2 cores
- **RAM:** 4 GB
- **Storage:** 20 GB SSD
- **OS:** Ubuntu 22.04 LTS or similar Linux distribution

### Required Software
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0+ or PostgreSQL 13+
- Nginx or Apache web server
- Node.js 18+ (for frontend assets)
- Redis (optional, for caching/sessions)

### PHP Extensions
```bash
php -m | grep -E 'pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo'
```

Required extensions:
- `php-cli`
- `php-fpm`
- `php-mysql` (or `php-pgsql`)
- `php-mbstring`
- `php-xml`
- `php-bcmath`
- `php-curl`
- `php-zip`
- `php-gd`
- `php-redis` (optional)

---

## Installation Steps

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/your-org/opf-cd.git
cd opf-cd/backend
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if frontend assets need building)
npm install
npm run build
```

### 3. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/opf-cd

# Set directory permissions
sudo find /var/www/opf-cd/backend -type d -exec chmod 755 {} \;
sudo find /var/www/opf-cd/backend -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 /var/www/opf-cd/backend/storage
sudo chmod -R 775 /var/www/opf-cd/backend/bootstrap/cache
```

---

## Database Setup

### 1. Create Database

**MySQL:**
```sql
CREATE DATABASE opf_capital_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'opf_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON opf_capital_dashboard.* TO 'opf_user'@'localhost';
FLUSH PRIVILEGES;
```

**PostgreSQL:**
```sql
CREATE DATABASE opf_capital_dashboard;
CREATE USER opf_user WITH ENCRYPTED PASSWORD 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON DATABASE opf_capital_dashboard TO opf_user;
```

### 2. Run Migrations

```bash
cd /var/www/opf-cd/backend

# Run all migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### 3. Seed Production Data

```bash
# Create admin user and role structure
php artisan db:seed --class=ProductionSeeder

# Output will show default admin credentials:
# Email: admin@opfcapital.com
# Password: ChangeMe123!
```

⚠️ **CRITICAL:** Change the admin password immediately after first login!

---

## Environment Configuration

### 1. Create .env File

```bash
cd /var/www/opf-cd/backend
cp .env.example .env
```

### 2. Configure Environment Variables

Edit `.env` with production values:

```bash
nano .env
```

**Critical Settings:**

```env
# Application
APP_NAME="OPF Capital Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.opfcapital.com

# Generate unique app key
php artisan key:generate

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=opf_capital_dashboard
DB_USERNAME=opf_user
DB_PASSWORD=YOUR_STRONG_DATABASE_PASSWORD

# Session (use database or redis in production)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Cache (use redis for better performance)
CACHE_STORE=redis

# Queue (use redis or database)
QUEUE_CONNECTION=redis

# Mail (configure your SMTP provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@opfcapital.com
MAIL_FROM_NAME="OPF Capital Dashboard"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Rate Limiting
API_RATE_LIMIT=60

# Backup
BACKUP_ENABLED=true
BACKUP_PATH=storage/backups
BACKUP_RETENTION_DAYS=30

# Health Check
HEALTH_CHECK_ENABLED=true
```

### 3. Optimize Configuration

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

---

## Security Hardening

### 1. SSL/HTTPS Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/opf-cd`):

```nginx
server {
    listen 80;
    server_name dashboard.opfcapital.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name dashboard.opfcapital.com;
    root /var/www/opf-cd/backend/public;

    # SSL Certificates
    ssl_certificate /etc/letsencrypt/live/dashboard.opfcapital.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/dashboard.opfcapital.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/opf-cd /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 2. Firewall Configuration

```bash
# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3. Secure File Permissions

```bash
# Ensure .env is not readable by web server
chmod 600 /var/www/opf-cd/backend/.env

# Ensure storage is writable
chmod -R 775 /var/www/opf-cd/backend/storage
```

### 4. Disable Directory Listing

Add to Nginx config or `.htaccess`:
```
Options -Indexes
```

---

## Running the Application

### 1. Start Queue Worker (for background jobs)

Create systemd service (`/etc/systemd/system/opf-queue.service`):

```ini
[Unit]
Description=OPF Capital Dashboard Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/opf-cd/backend
ExecStart=/usr/bin/php /var/www/opf-cd/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable opf-queue
sudo systemctl start opf-queue
sudo systemctl status opf-queue
```

### 2. Schedule Cron Jobs

Add to crontab (`sudo crontab -e -u www-data`):

```cron
* * * * * cd /var/www/opf-cd/backend && php artisan schedule:run >> /dev/null 2>&1
```

This handles:
- Alert generation (daily)
- Cleanup tasks
- Scheduled reports

---

## Post-Deployment Verification

### 1. Health Check

```bash
curl https://dashboard.opfcapital.com/api/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2026-02-14T10:30:00Z",
  "environment": "production",
  "checks": {
    "application": {"status": "healthy"},
    "database": {"status": "healthy"},
    "cache": {"status": "healthy"},
    "storage": {"status": "healthy"}
  }
}
```

### 2. Login Test

1. Navigate to `https://dashboard.opfcapital.com/login`
2. Login with admin credentials:
   - Email: `admin@opfcapital.com`
   - Password: `ChangeMe123!`
3. **Immediately change password** via user settings

### 3. Dashboard Access

Verify all dashboards load:
- `/dashboard` - Main dashboard
- `/projects` - Projects dashboard
- `/finance/cash-flow` - Cash flow dashboard
- `/sales/pipeline` - Sales pipeline

### 4. Run Test Suite (optional)

```bash
php artisan test
```

All tests should pass.

---

## Backup & Recovery

### 1. Automated Database Backup

Create backup script (`/var/www/opf-cd/scripts/backup-database.sh`):

```bash
#!/bin/bash
# See scripts/backup-database.sh for full implementation
```

### 2. Schedule Backups

Add to crontab:
```cron
0 2 * * * /var/www/opf-cd/scripts/backup-database.sh
```

### 3. Test Recovery

```bash
# Test database restore
mysql -u opf_user -p opf_capital_dashboard < backup-2026-02-14.sql
```

### 4. Offsite Backup

Configure automated transfer to offsite location (S3, Azure Blob, etc.)

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Check logs:**
```bash
tail -f /var/www/opf-cd/backend/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

**Common causes:**
- Wrong file permissions on storage/
- Database connection failure
- Missing PHP extensions
- Cached configuration outdated

**Fix:**
```bash
chmod -R 775 storage/
php artisan config:clear
php artisan cache:clear
```

### Issue: Database Connection Failed

**Verify:**
```bash
mysql -u opf_user -p opf_capital_dashboard
```

**Check .env:**
- DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD correct
- MySQL service running: `sudo systemctl status mysql`

### Issue: Queue Jobs Not Processing

**Check queue worker:**
```bash
sudo systemctl status opf-queue
sudo journalctl -u opf-queue -f
```

**Restart worker:**
```bash
sudo systemctl restart opf-queue
```

### Issue: Health Check Failing

**Run diagnostics:**
```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

---

## Monitoring & Maintenance

### 1. Log Rotation

Configure logrotate (`/etc/logrotate.d/opf-cd`):

```
/var/www/opf-cd/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        sudo systemctl reload php8.2-fpm
    endscript
}
```

### 2. Disk Space Monitoring

Add alert when disk usage > 80%:
```bash
df -h | awk '$5 > 80 {print $0}'
```

### 3. Database Size Monitoring

```sql
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'opf_capital_dashboard'
GROUP BY table_schema;
```

---

## Rollback Procedure

If deployment fails:

### 1. Database Rollback

```bash
php artisan migrate:rollback --step=1
```

### 2. Code Rollback

```bash
git checkout previous-stable-tag
composer install --no-dev
php artisan config:cache
php artisan route:cache
```

### 3. Restore Database Backup

```bash
mysql -u opf_user -p opf_capital_dashboard < /path/to/last-good-backup.sql
```

---

## Support & Contacts

- **Technical Issues:** tech-support@opfcapital.com
- **Security Issues:** security@opfcapital.com
- **Documentation:** https://docs.opfcapital.com

---

## Checklist

Use this checklist for deployment:

- [ ] Server meets minimum requirements
- [ ] All required PHP extensions installed
- [ ] Code repository cloned
- [ ] Dependencies installed (Composer, npm)
- [ ] Database created and credentials configured
- [ ] Migrations run successfully
- [ ] Production seeder run (admin user created)
- [ ] .env file configured with production values
- [ ] APP_KEY generated
- [ ] SSL certificate installed
- [ ] Nginx/Apache configured with HTTPS
- [ ] File permissions set correctly
- [ ] Queue worker service running
- [ ] Cron jobs scheduled
- [ ] Health check endpoint returning 200 OK
- [ ] Can login with admin credentials
- [ ] Admin password changed immediately
- [ ] All dashboards accessible
- [ ] Backup script configured and tested
- [ ] Monitoring configured
- [ ] Firewall rules applied
- [ ] Log rotation configured
- [ ] Documentation reviewed

---

**Deployment Date:** _____________  
**Deployed By:** _____________  
**Version:** _____________  
**Notes:** _____________
