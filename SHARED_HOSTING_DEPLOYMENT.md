# Shared Hosting Deployment Guide
## OPF-CD Deployment to Hostinger (opf-cd.3bs.ltd)

**Target:** `/home/u266222025/domains/3bs.ltd/public_html/opf-cd`
**URL:** https://opf-cd.3bs.ltd

---

## Pre-Deployment Setup

### 1. Local Preparation

```bash
cd C:\Users\DELL\opf-cd\backend

# Install dependencies (with optimized autoloader)
composer install --optimize-autoloader --no-dev

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Generate optimized configs
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Create Production .env File

Create `backend/.env.production` with these settings:

```env
APP_NAME="OPF-CD"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://opf-cd.3bs.ltd

# Database (Get from Hostinger panel)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u266222025_opfcd
DB_USERNAME=u266222025_opfcd
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

# Session & Cache (file-based for shared hosting)
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
CACHE_PREFIX=opfcd_

# Rate Limiting
THROTTLE_PER_MINUTE=60

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null
```

---

## Hostinger Panel Configuration

### Step 1: Create Database (via Hostinger Panel)

1. Login to Hostinger Panel
2. Go to **Databases** → **MySQL Databases**
3. Create new database:
   - **Name:** `u266222025_opfcd`
   - **Username:** `u266222025_opfcd`
   - **Password:** Generate strong password
4. Note credentials for .env file

### Step 2: Create Subdomain

1. Go to **Domains** → **Subdomains**
2. Create subdomain: `opf-cd.3bs.ltd`
3. Document root: `/home/u266222025/domains/3bs.ltd/public_html/opf-cd/public`

---

## File Upload via FTP

### Files to Upload:

```
Upload to: /home/u266222025/domains/3bs.ltd/public_html/opf-cd/

backend/
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/          # This will be document root
├── resources/
├── routes/
├── storage/
│   ├── app/
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/
│   │   └── views/
│   └── logs/
├── vendor/          # Upload entire vendor folder
├── .env.production  # Rename to .env after upload
├── artisan
└── composer.json
```

### FTP Connection Details:
- **Host:** 194.195.84.188 or ftp.3bs.ltd
- **Username:** u266222025
- **Password:** [Your Hostinger password]
- **Port:** 21

### Using FileZilla:

1. Connect via FTP
2. Navigate to: `/domains/3bs.ltd/public_html/`
3. Create folder: `opf-cd`
4. Upload entire `backend/` contents to `opf-cd/`
5. **Important:** Upload the `vendor/` folder (this will take time)

---

## Server Configuration

### Step 1: Set Document Root (Hostinger Panel)

1. Go to **Hosting** → **Website Settings**
2. For subdomain `opf-cd.3bs.ltd`:
   - Document Root: `/home/u266222025/domains/3bs.ltd/public_html/opf-cd/public`
3. Save changes

### Step 2: Configure .htaccess

Create `/public_html/opf-cd/public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Step 3: Set File Permissions (via FTP or File Manager)

```
storage/ → 755 (folders) / 644 (files)
storage/framework/ → 755
storage/framework/cache/ → 755
storage/framework/sessions/ → 755
storage/framework/views/ → 755
storage/logs/ → 755
bootstrap/cache/ → 755
```

### Step 4: Security - Move .env Outside Public

Via FTP/File Manager:
1. Move `.env` one level up from `public/`
2. It should be at: `/opf-cd/.env` (NOT in `/opf-cd/public/.env`)

---

## Database Setup

### Option A: Using Web Migration Script (Recommended)

1. Upload this script to `/opf-cd/public/migrate.php`:

```php
<?php
// migrate.php - Run once via browser, then DELETE this file!

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Starting migrations...\n\n";

try {
    // Run migrations
    $kernel->call('migrate', ['--force' => true]);
    echo "\n✅ Migrations completed successfully!\n\n";
    
    // Run production seeder
    $kernel->call('db:seed', ['--class' => 'ProductionSeeder', '--force' => true]);
    echo "\n✅ Production seeder completed!\n\n";
    
    echo "Default admin user:\n";
    echo "Email: admin@opf-cd.test\n";
    echo "Password: password123\n\n";
    echo "⚠️ IMPORTANT: Delete this file (migrate.php) immediately!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
```

2. Visit: `https://opf-cd.3bs.ltd/migrate.php`
3. **IMMEDIATELY DELETE** `migrate.php` after successful run

### Option B: Using phpMyAdmin

1. Go to Hostinger Panel → **phpMyAdmin**
2. Select database: `u266222025_opfcd`
3. Import each SQL file from `database/migrations/` in order:
   - 001_create_users_table.sql
   - 002_create_roles_permissions_tables.sql
   - ...all migration files in numerical order

---

## Post-Deployment Verification

### 1. Test Health Check
```
https://opf-cd.3bs.ltd/api/health
```

Should return:
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

### 2. Test Login Page
```
https://opf-cd.3bs.ltd/login
```

Default credentials:
- Email: `admin@opf-cd.test`
- Password: `password123`

### 3. Test Dashboard
```
https://opf-cd.3bs.ltd/dashboard
```

---

## Scheduled Tasks (Cron Jobs)

### Setup via Hostinger Panel:

1. Go to **Advanced** → **Cron Jobs**
2. Add cron job:

```bash
# Run Laravel scheduler every minute
* * * * * cd /home/u266222025/domains/3bs.ltd/public_html/opf-cd && php artisan schedule:run >> /dev/null 2>&1
```

This handles:
- Alert generation (daily)
- Recurring expense generation
- Any scheduled tasks

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Fix:**
1. Check `storage/logs/laravel.log` via FTP
2. Ensure `.env` file exists and is configured correctly
3. Check file permissions on `storage/` and `bootstrap/cache/`
4. Clear config cache by deleting: `bootstrap/cache/config.php`

### Issue: Database Connection Failed

**Fix:**
1. Verify database credentials in `.env`
2. Check database exists in Hostinger panel
3. Ensure DB_HOST=localhost (not 127.0.0.1)
4. Try DB_HOST with socket: `localhost:/var/lib/mysql/mysql.sock`

### Issue: "No application encryption key has been specified"

**Fix:**
1. Generate new key locally: `php artisan key:generate --show`
2. Copy the key to `.env` file on server:
   ```
   APP_KEY=base64:YOUR_GENERATED_KEY_HERE
   ```

### Issue: Permissions Errors

**Fix via FTP:**
```
chmod 755 storage
chmod 755 storage/framework
chmod 755 storage/framework/cache
chmod 755 storage/framework/sessions
chmod 755 storage/framework/views
chmod 755 storage/logs
chmod 755 bootstrap/cache
```

### Issue: Session/Cache Not Working

**Fix in .env:**
```env
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

---

## Security Hardening

### 1. Disable Directory Listing

Add to `.htaccess`:
```apache
Options -Indexes
```

### 2. Change Default Admin Password

1. Login with default credentials
2. Go to user profile
3. Change password immediately

### 3. Update Production .env

After deployment:
```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
```

### 4. Remove Test Scripts

Delete these files:
- `/public/migrate.php` (if used)
- `/backend/test_audit_logging.php`
- `/backend/test_dashboard_apis.php`
- `/backend/assign_admin.php`
- `/backend/add_enums.php`

---

## Maintenance

### Updating Code:

1. Make changes locally
2. Test thoroughly
3. Upload changed files via FTP
4. Clear caches by deleting:
   - `bootstrap/cache/config.php`
   - `bootstrap/cache/routes-v7.php`
   - Files in `storage/framework/views/`

### Database Backups:

**Via Hostinger Panel:**
1. Go to **Databases** → **phpMyAdmin**
2. Select `u266222025_opfcd`
3. Click **Export** → **Quick** → **Go**
4. Download SQL file

**Via Cron (if available):**
```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/mysqldump -u u266222025_opfcd -p'PASSWORD' u266222025_opfcd > /home/u266222025/backups/opfcd_$(date +\%Y\%m\%d).sql
```

---

## Limitations on Shared Hosting

**What WON'T Work:**
- ❌ Queue workers (background jobs)
- ❌ WebSocket connections
- ❌ Long-running processes
- ❌ Real-time notifications
- ❌ Custom PHP extensions

**What DOES Work:**
- ✅ All CRUD operations
- ✅ Dashboard displays
- ✅ Audit logging
- ✅ Report exports (CSV)
- ✅ Scheduled tasks (via cron)
- ✅ File uploads
- ✅ Session management

---

## Support

**Hostinger Support:**
- Live Chat: 24/7 via Hostinger Panel
- Knowledge Base: https://support.hostinger.com

**For deployment issues:**
1. Check `storage/logs/laravel.log`
2. Enable debug mode temporarily: `APP_DEBUG=true`
3. Check PHP error logs in Hostinger panel
4. Verify database connection
5. Ensure all files uploaded correctly

---

## Quick Reference

**FTP:**
- Host: 194.195.84.188
- User: u266222025
- Path: /domains/3bs.ltd/public_html/opf-cd

**Database:**
- Host: localhost
- Database: u266222025_opfcd
- User: u266222025_opfcd

**URLs:**
- Production: https://opf-cd.3bs.ltd
- Health Check: https://opf-cd.3bs.ltd/api/health
- Login: https://opf-cd.3bs.ltd/login

**Default Admin:**
- Email: admin@opf-cd.test
- Password: password123
- **CHANGE IMMEDIATELY AFTER FIRST LOGIN!**

---

**Last Updated:** February 14, 2026
