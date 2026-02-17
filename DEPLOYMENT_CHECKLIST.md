# Shared Hosting Deployment Checklist
## OPF-CD → Hostinger (opf-cd.3bs.ltd)

Follow this checklist in order for successful deployment.

---

## ☑️ Phase 1: Local Preparation (15 minutes)

### 1.1 Install Production Dependencies
```bash
cd C:\Users\DELL\opf-cd\backend
composer install --optimize-autoloader --no-dev
```
- [ ] Composer dependencies installed
- [ ] Vendor folder generated

### 1.2 Generate App Key
```bash
php artisan key:generate --show
```
- [ ] Copy the generated key (starts with `base64:`)
- [ ] Save it for .env configuration

### 1.3 Clear All Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```
- [ ] All caches cleared

### 1.4 Create Production .env
```bash
cp .env.example .env.production
```
Edit `.env.production` with:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://opf-cd.3bs.ltd
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u266222025_opfcd
DB_USERNAME=u266222025_opfcd
DB_PASSWORD=[TO BE FILLED FROM HOSTINGER]
SESSION_DRIVER=file
CACHE_DRIVER=file
```
- [ ] .env.production created
- [ ] APP_KEY added (from step 1.2)

---

## ☑️ Phase 2: Hostinger Panel Setup (10 minutes)

### 2.1 Create Database
1. Login to Hostinger Panel
2. Navigate to: **Databases** → **MySQL Databases**
3. Click **Create New Database**
4. Database settings:
   - Name: `u266222025_opfcd`
   - User: `u266222025_opfcd`
   - Password: Generate strong password

- [ ] Database created
- [ ] Credentials saved
- [ ] Password added to .env.production

### 2.2 Create Subdomain
1. Navigate to: **Domains** → **Subdomains**
2. Click **Create Subdomain**
3. Subdomain settings:
   - Name: `opf-cd`
   - Parent domain: `3bs.ltd`
   - Document root: `/public_html/opf-cd/public`

- [ ] Subdomain created: opf-cd.3bs.ltd
- [ ] Document root set correctly

### 2.3 Set PHP Version
1. Navigate to: **Advanced** → **PHP Configuration**
2. Select subdomain: `opf-cd.3bs.ltd`
3. Set PHP version: **8.2** (or latest available)

- [ ] PHP 8.2+ selected

---

## ☑️ Phase 3: File Upload (30-60 minutes)

### 3.1 Connect via FTP
**FileZilla Settings:**
- Host: `194.195.84.188` or `ftp.3bs.ltd`
- Username: `u266222025`
- Password: [Your Hostinger password]
- Port: `21`

- [ ] Connected to FTP server
- [ ] Navigated to `/domains/3bs.ltd/public_html/`

### 3.2 Create Project Folder
- [ ] Created folder: `opf-cd`
- [ ] Entered: `/public_html/opf-cd/`

### 3.3 Upload Files (in this order)

**Priority uploads (upload first):**
1. [ ] `vendor/` folder (largest, ~150MB, takes 20-40 mins)
2. [ ] `app/` folder
3. [ ] `config/` folder
4. [ ] `routes/` folder
5. [ ] `database/` folder (migrations & seeders)

**Secondary uploads:**
6. [ ] `bootstrap/` folder
7. [ ] `public/` folder
8. [ ] `resources/` folder
9. [ ] `storage/` folder
10. [ ] `.env.production` (rename to `.env` after upload)
11. [ ] `artisan`
12. [ ] `composer.json`
13. [ ] `composer.lock`

**Files to SKIP (don't upload):**
- [ ] ❌ `.git/` folder
- [ ] ❌ `node_modules/`
- [ ] ❌ `.env.example`
- [ ] ❌ Test scripts (test_*.php, assign_admin.php)

### 3.4 Rename .env File
```
Rename: .env.production → .env
```
- [ ] .env file renamed

---

## ☑️ Phase 4: Server Configuration (10 minutes)

### 4.1 Set File Permissions (via File Manager)

Navigate to each folder and set permissions:
```
storage/ → 755 (all subfolders)
storage/framework/cache → 755
storage/framework/sessions → 755
storage/framework/views → 755
storage/logs → 755
bootstrap/cache → 755
```

**Via File Manager:**
1. Right-click folder → **Change Permissions**
2. Set to: **755** (rwxr-xr-x)

- [ ] storage/ permissions set
- [ ] storage/framework/ permissions set
- [ ] bootstrap/cache/ permissions set

### 4.2 Verify .htaccess
Check `/opf-cd/public/.htaccess` exists with:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

- [ ] .htaccess file exists in public folder
- [ ] Contains mod_rewrite rules

---

## ☑️ Phase 5: Database Setup (5 minutes)

### Option A: Web Migration Script (Recommended)

1. Visit: `https://opf-cd.3bs.ltd/migrate.php`
2. Wait for setup to complete (30-60 seconds)
3. Note default admin credentials
4. **Immediately**: Delete `migrate.php` file

- [ ] Visited migrate.php
- [ ] Saw success message
- [ ] Noted admin credentials
- [ ] **DELETED migrate.php file**

### Option B: Manual via phpMyAdmin

If web script failed:
1. Go to Hostinger Panel → **phpMyAdmin**
2. Select database: `u266222025_opfcd`
3. Go to **Import** tab
4. Upload each SQL file from `database/migrations/` in order

- [ ] Alternative: All migrations imported manually

---

## ☑️ Phase 6: Verification & Testing (10 minutes)

### 6.1 Test Health Check
Visit: `https://opf-cd.3bs.ltd/api/health`

Expected response:
```json
{
    "status": "healthy",
    "checks": {
        "application": "ok",
        "database": "ok",
        "cache": "ok",
        "storage": "ok"
    }
}
```

- [ ] Health check returns "healthy"
- [ ] All checks show "ok"

### 6.2 Test Login Page
Visit: `https://opf-cd.3bs.ltd/login`

- [ ] Login page loads without errors
- [ ] Sees styled login form

### 6.3 Test Admin Login
Login with:
- Email: `admin@opf-cd.test`
- Password: `password123`

- [ ] Successfully logged in
- [ ] Redirected to dashboard

### 6.4 Test Dashboard
Visit: `https://opf-cd.3bs.ltd/dashboard`

- [ ] Dashboard loads
- [ ] Sees metric cards
- [ ] No console errors (F12)

### 6.5 Test Each Dashboard
Click through:
- [ ] Project Progress
- [ ] Payment Gap
- [ ] Project Health
- [ ] Cash Flow (shows data)
- [ ] Upcoming Expenses (shows expenses)
- [ ] Sales Pipeline (shows opportunities)

---

## ☑️ Phase 7: Security Hardening (5 minutes)

### 7.1 Change Admin Password
1. Login as admin
2. Go to profile/settings
3. Change password to strong password

- [ ] Admin password changed
- [ ] New password saved securely

### 7.2 Verify Production Settings
Check `.env` file:
```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
```

- [ ] APP_DEBUG is false
- [ ] APP_ENV is production

### 7.3 Remove Test Files
Delete via File Manager:
- [ ] `backend/test_audit_logging.php`
- [ ] `backend/test_dashboard_apis.php`
- [ ] `backend/assign_admin.php`
- [ ] `backend/add_enums.php`
- [ ] `public/migrate.php` (if not already deleted)

---

## ☑️ Phase 8: Setup Cron Jobs (5 minutes)

### 8.1 Configure Laravel Scheduler
1. Go to Hostinger Panel → **Advanced** → **Cron Jobs**
2. Click **Create Cron Job**
3. Settings:
   - **Frequency:** Every minute
   - **Command:**
     ```bash
     cd /home/u266222025/domains/3bs.ltd/public_html/opf-cd && php artisan schedule:run >> /dev/null 2>&1
     ```

- [ ] Cron job created
- [ ] Set to run every minute

---

## ☑️ Phase 9: Backup Configuration (5 minutes)

### 9.1 Setup Database Backups
1. Go to **Databases** → **phpMyAdmin**
2. Select database `u266222025_opfcd`
3. Click **Export**
4. Save SQL file to safe location

- [ ] Manual backup created
- [ ] Backup file saved locally

### 9.2 Document Credentials
Create secure note with:
- [ ] FTP credentials
- [ ] Database credentials
- [ ] Admin login credentials
- [ ] Hostinger panel login

---

## ☑️ Phase 10: Post-Deployment (10 minutes)

### 10.1 Create First Real User
1. Login as admin
2. Go to Users section (if available)
3. Create a real admin user with your email

- [ ] Real admin user created

### 10.2 Test All CRUD Operations
Quick test of each module:
- [ ] Create test project
- [ ] Create test task
- [ ] Create test expense
- [ ] Create test opportunity
- [ ] Create test account
- [ ] Record test transaction

### 10.3 Test Reports
- [ ] Export CSV report works
- [ ] Download completes successfully

### 10.4 Test Audit Logging
- [ ] View audit logs
- [ ] See recent actions logged

---

## ✅ Deployment Complete!

**Deployed Application:**
- URL: https://opf-cd.3bs.ltd
- Admin: [Your new admin email]
- Status: Production

**What's Working:**
- ✅ All dashboards displaying
- ✅ CRUD operations functional
- ✅ Audit logging active
- ✅ Reports exporting
- ✅ Scheduled tasks running

**What to Monitor:**
- Check `storage/logs/laravel.log` daily for first week
- Monitor disk space usage
- Review audit logs regularly
- Test backups weekly

---

## 🚨 Troubleshooting Quick Reference

**500 Error:**
- Check `storage/logs/laravel.log`
- Verify file permissions (755 for storage/)
- Clear bootstrap/cache/config.php

**Database Error:**
- Check DB credentials in .env
- Test connection via phpMyAdmin
- Ensure DB_HOST=localhost

**Session Issues:**
- Verify SESSION_DRIVER=file in .env
- Check storage/framework/sessions/ permissions

**Need Help:**
- Hostinger Support: 24/7 live chat
- Check: SHARED_HOSTING_DEPLOYMENT.md

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Sign-off:** _______________
