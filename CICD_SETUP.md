# CI/CD Setup Guide
## Professional Automated Deployment for OPF-CD

This guide walks you through setting up **automated deployment** with GitHub Actions.

---

## 🎯 What You Get

✅ **Auto-deploy on push to master** - No manual deployment needed
✅ **Zero-downtime deployments** - Users experience no interruption  
✅ **Rollback capability** - Keep last 3 releases for instant rollback
✅ **Automated testing** (optional) - Tests run before deployment
✅ **Deployment history** - Track all deployments in GitHub Actions
✅ **Manual triggers** - Deploy manually from GitHub UI when needed

---

## 📋 Prerequisites

Before starting, ensure you have:

- [x] GitHub repository with your code
- [x] Production server with SSH access
- [x] SSH key authentication configured (not password)
- [x] Git installed on server
- [x] Composer installed on server
- [x] PHP 8.2+ on server

---

## 🔧 Setup Instructions

### Step 1: Prepare Server (One-time Setup)

#### 1.1 SSH into Your Server

```bash
ssh your-username@your-server.com
```

#### 1.2 Create Deployment Directory Structure

```bash
# Navigate to your web root (adjust path as needed)
cd /home/u266222025/domains/3bs.ltd/public_html/

# Create deployment directory
mkdir -p opf-cd
cd opf-cd

# Clone repository
git clone https://github.com/3bsolutionsltd/opf-cd.git .

# Make deploy script executable
chmod +x deploy.sh

# Create shared .env file
cp backend/.env.example shared/.env

# Edit .env with production settings
nano shared/.env
```

**Configure your `shared/.env`:**
```env
APP_NAME="OPF-CD"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://opf-cd.3bs.ltd

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### 1.3 Initial Manual Deployment

Run the deployment script manually for the first time:

```bash
cd /path/to/opf-cd
bash deploy.sh
```

This will:
- Create release directories
- Setup shared storage
- Run migrations
- Create `current` symlink

#### 1.4 Configure Web Server Document Root

**Point your subdomain to:**
```
/path/to/opf-cd/current/public
```

Example: `/home/u266222025/domains/3bs.ltd/public_html/opf-cd/current/public`

---

### Step 2: Generate SSH Key for GitHub Actions

#### 2.1 Generate New SSH Key (on your local machine)

```bash
# Generate SSH key specifically for deployment
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/opf-cd-deploy

# This creates two files:
# - opf-cd-deploy (private key) - for GitHub Secrets
# - opf-cd-deploy.pub (public key) - for server
```

#### 2.2 Add Public Key to Server

```bash
# Copy public key content
cat ~/.ssh/opf-cd-deploy.pub

# SSH into server
ssh your-username@your-server.com

# Add public key to authorized_keys
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys
# Paste the public key content
# Save and exit (Ctrl+X, Y, Enter)

chmod 600 ~/.ssh/authorized_keys
```

#### 2.3 Test SSH Key

```bash
# Test connection from local machine
ssh -i ~/.ssh/opf-cd-deploy your-username@your-server.com

# Should connect without password
```

---

### Step 3: Configure GitHub Secrets

#### 3.1 Add Secrets to GitHub Repository

Go to: **GitHub Repository** → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Add these secrets:

| Secret Name | Value | Example |
|-------------|-------|---------|
| `PROD_HOST` | Server IP or hostname | `194.195.84.188` or `server738.web-hosting.com` |
| `PROD_USERNAME` | SSH username | `u266222025` |
| `PROD_SSH_KEY` | Private key content | Contents of `~/.ssh/opf-cd-deploy` (entire file) |
| `PROD_SSH_PORT` | SSH port | `22` (or custom port) |
| `PROD_DEPLOY_PATH` | Deployment directory | `/home/u266222025/domains/3bs.ltd/public_html/opf-cd` |
| `PROD_URL` | Production URL | `https://opf-cd.3bs.ltd` |

**To copy private key content:**
```bash
# macOS/Linux
cat ~/.ssh/opf-cd-deploy | pbcopy

# Windows (PowerShell)
Get-Content ~/.ssh/opf-cd-deploy | Set-Clipboard

# Or just cat and copy manually
cat ~/.ssh/opf-cd-deploy
```

---

### Step 4: Commit and Push CI/CD Files

```bash
# From your local repo
cd C:\Users\DELL\opf-cd

# Add new files
git add .github/workflows/deploy.yml
git add deploy.sh
git add CICD_SETUP.md
git add DEPLOYMENT_PROCESS.md

# Commit
git commit -m "Add CI/CD pipeline for automated deployment"

# Push to master
git push origin master
```

**This first push will trigger your first automated deployment!** 🚀

---

### Step 5: Monitor Deployment

#### 5.1 Watch GitHub Actions

1. Go to your GitHub repository
2. Click **Actions** tab
3. You'll see "Deploy to Production" workflow running
4. Click on the workflow to see live logs

#### 5.2 Check Deployment Status

Watch for:
- ✅ Checkout Code
- ✅ Setup PHP
- ✅ Install Dependencies
- ✅ Run Tests (if enabled)
- ✅ Deploy to Production
- ✅ Deployment Complete

#### 5.3 Verify on Server

```bash
# SSH into server
ssh your-username@your-server.com

# Check current release
ls -la /path/to/opf-cd/current

# Check release history
ls -la /path/to/opf-cd/releases/

# Check application logs
tail -f /path/to/opf-cd/shared/storage/logs/laravel.log
```

#### 5.4 Test Application

Visit: `https://opf-cd.3bs.ltd`

- [ ] Application loads without errors
- [ ] Login works
- [ ] Dashboard displays correctly

---

## 🔄 Daily Workflow (After Setup)

### For Developers:

```bash
# 1. Make code changes locally
# 2. Test locally
# 3. Commit changes
git add .
git commit -m "Your change description"

# 4. Push to master - deployment happens automatically!
git push origin master

# 5. Watch deployment in GitHub Actions tab
# 6. Verify on production URL
```

**That's it!** No manual deployment needed.

---

## 🎛️ Manual Deployment Triggers

### Option 1: GitHub UI (Easiest)

1. Go to **GitHub** → **Actions** tab
2. Click **Deploy to Production** workflow
3. Click **Run workflow** button
4. Select `master` branch
5. Click **Run workflow**

### Option 2: Via SSH (Direct)

```bash
# SSH into server
ssh your-username@your-server.com

# Run deployment script
cd /path/to/opf-cd
git pull origin master
bash deploy.sh
```

---

## ⏮️ Rollback to Previous Release

If something goes wrong, rollback is instant:

### Method 1: Symlink Switch (Instant - Recommended)

```bash
# SSH into server
ssh your-username@your-server.com

# List available releases
ls -la /path/to/opf-cd/releases/

# Output example:
# release_20260216_143022 (current)
# release_20260216_120045 (previous)
# release_20260215_180012 (older)

# Switch to previous release
cd /path/to/opf-cd
rm current
ln -s releases/release_20260216_120045 current

# Reload services
sudo systemctl reload php-fpm || true
```

**Done!** Instant rollback with zero downtime.

### Method 2: Redeploy Old Commit

```bash
# From local machine
git log --oneline  # Find commit hash you want

# Revert to old commit (creates new commit)
git revert <commit-hash>
git push origin master

# Or force reset (rewrites history - use carefully)
git reset --hard <commit-hash>
git push --force origin master
```

---

## 🔍 Troubleshooting

### Deployment Failed: SSH Connection Error

**Symptoms:** GitHub Actions shows "Connection refused" or "Permission denied"

**Fix:**
```bash
# 1. Verify SSH key is correct in GitHub Secrets
# 2. Test SSH connection from local machine
ssh -i ~/.ssh/opf-cd-deploy your-username@your-server.com

# 3. Check authorized_keys on server
cat ~/.ssh/authorized_keys | grep github-actions

# 4. Check SSH logs on server
sudo tail -f /var/log/auth.log
```

### Deployment Failed: Permission Denied

**Symptoms:** "Permission denied" during file operations

**Fix:**
```bash
# SSH into server
ssh your-username@your-server.com

# Fix ownership
cd /path/to/opf-cd
chown -R your-username:your-username .

# Fix permissions
chmod -R 755 releases shared
chmod 755 deploy.sh
```

### Deployment Failed: Composer Install Error

**Symptoms:** "composer install" fails in GitHub Actions

**Fix:**
```bash
# SSH into server
ssh your-username@your-server.com

# Update composer
composer self-update

# Test composer locally
cd /path/to/opf-cd
composer install --no-dev --optimize-autoloader
```

### Deployment Succeeds but Site Shows 500 Error

**Symptoms:** Deployment completes but site returns 500 error

**Fix:**
```bash
# SSH into server
ssh your-username@your-server.com

# Check Laravel logs
tail -100 /path/to/opf-cd/shared/storage/logs/laravel.log

# Common fixes:

# 1. Fix permissions
chmod -R 755 /path/to/opf-cd/shared/storage
chmod -R 755 /path/to/opf-cd/current/bootstrap/cache

# 2. Clear caches
cd /path/to/opf-cd/current
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 3. Verify .env
cat /path/to/opf-cd/shared/.env | grep APP_KEY
# APP_KEY should have a value

# 4. Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### GitHub Actions Workflow Not Triggering

**Symptoms:** Push to master but no deployment happens

**Fix:**
1. Check **GitHub** → **Actions** tab
2. Look for error messages
3. Verify workflow file is in `.github/workflows/deploy.yml`
4. Check branch name matches (case-sensitive):
   ```bash
   git branch --show-current
   # Should show: master
   ```

---

## 🔒 Security Best Practices

### 1. SSH Key Rotation

Rotate deployment SSH keys every 6-12 months:

```bash
# Generate new key
ssh-keygen -t ed25519 -C "github-actions-deploy-2026" -f ~/.ssh/opf-cd-deploy-new

# Add new key to server
ssh-copy-id -i ~/.ssh/opf-cd-deploy-new.pub user@server

# Update GitHub Secret: PROD_SSH_KEY

# Test deployment with new key

# Remove old key from server
nano ~/.ssh/authorized_keys
# Delete old key line
```

### 2. Restrict SSH Key Permissions

On server, limit what the deployment key can do:

```bash
nano ~/.ssh/authorized_keys

# Prefix the key with restrictions:
command="cd /path/to/opf-cd && bash deploy.sh",no-agent-forwarding,no-X11-forwarding,no-port-forwarding ssh-ed25519 AAAA...
```

### 3. Monitor Deployment Activity

- Review **GitHub Actions** logs regularly
- Set up deployment notifications (see optional section below)
- Monitor application logs after each deployment

### 4. Protect Master Branch

**GitHub** → **Settings** → **Branches** → **Add rule** for `master`:
- [x] Require pull request reviews before merging
- [x] Require status checks to pass before merging
- [x] Include administrators

---

## 📊 Optional: Add Slack Notifications

### 1. Create Slack Incoming Webhook

1. Go to Slack App Directory
2. Search for "Incoming Webhooks"
3. Add to your workspace
4. Create webhook for deployment channel
5. Copy webhook URL

### 2. Add to GitHub Secrets

**GitHub** → **Settings** → **Secrets** → **New secret**:
- Name: `SLACK_WEBHOOK`
- Value: `https://hooks.slack.com/services/...`

### 3. Uncomment Notification Job

Edit `.github/workflows/deploy.yml`:

```yaml
# Remove comment markers from notify job at bottom of file
notify:
  name: Send Notifications
  runs-on: ubuntu-latest
  needs: deploy
  if: always()
  steps:
    - name: 📢 Slack Notification
      # ... rest of config
```

### 4. Test Notification

Push any change to master - you'll get Slack notification!

---

## 📈 Monitoring & Health Checks

### Enable Health Check Endpoint

The deployment script has commented health check code. To enable:

```bash
# Edit deploy.sh
nano /path/to/opf-cd/deploy.sh

# Find and uncomment these lines near the end:
HEALTH_URL=$(grep APP_URL "${SHARED_DIR}/.env" | cut -d '=' -f2 | tr -d '"')/api/health
if command -v curl &> /dev/null; then
    log_info "Running health check: ${HEALTH_URL}"
    HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_URL}")
    if [ "${HEALTH_STATUS}" = "200" ]; then
        log_success "Health check passed"
    else
        log_warning "Health check returned: ${HEALTH_STATUS}"
    fi
fi
```

### Verify Health Endpoint Works

```bash
# Test health endpoint
curl https://opf-cd.3bs.ltd/api/health

# Expected response:
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

---

## 🎓 Advanced: Multiple Environments (Staging + Production)

If you want to add staging environment later:

### 1. Create Staging Branch

```bash
git checkout -b staging
git push origin staging
```

### 2. Setup Staging Server

Same setup as production but different server/subdomain

### 3. Add Staging Secrets to GitHub

- `STAGING_HOST`
- `STAGING_USERNAME`
- `STAGING_SSH_KEY`
- `STAGING_SSH_PORT`
- `STAGING_DEPLOY_PATH`
- `STAGING_URL`

### 4. Update Workflow

Add staging deploy job that triggers on `staging` branch push.

---

## 📚 Reference

### Deployment Directory Structure

```
/path/to/opf-cd/
├── current -> releases/release_20260216_143022/  (symlink)
├── releases/
│   ├── release_20260216_143022/  (current)
│   ├── release_20260216_120045/  (previous)
│   └── release_20260215_180012/  (older)
├── shared/
│   ├── .env
│   └── storage/
│       ├── app/
│       ├── framework/
│       └── logs/
├── deploy.sh
└── .git/
```

### Key Commands

```bash
# Deploy manually
cd /path/to/opf-cd && bash deploy.sh

# Check current release
ls -la /path/to/opf-cd/current

# View deployment logs
tail -f /path/to/opf-cd/shared/storage/logs/laravel.log

# Rollback
rm /path/to/opf-cd/current
ln -s /path/to/opf-cd/releases/release_OLD_TIMESTAMP /path/to/opf-cd/current
```

---

## ✅ Post-Setup Checklist

- [ ] Server deployment directory created
- [ ] SSH key generated and added to server
- [ ] GitHub Secrets configured (6 secrets)
- [ ] Initial manual deployment successful
- [ ] Web server document root points to `current/public`
- [ ] First automated deployment from GitHub push successful
- [ ] Application accessible and working
- [ ] Health check endpoint working (optional)
- [ ] Rollback tested successfully
- [ ] Team notified of new deployment process

---

## 🎉 You're Done!

Your CI/CD pipeline is now live! Every push to `master` will automatically deploy to production with zero downtime.

**Next Steps:**
- Read [DEPLOYMENT_PROCESS.md](DEPLOYMENT_PROCESS.md) for daily workflow
- Share this guide with your team
- Monitor first few deployments closely
- Celebrate! 🚀

---

**Need Help?**
- Check GitHub Actions logs for detailed error messages
- Review deployment script output on server
- Check Laravel logs: `/path/to/opf-cd/shared/storage/logs/laravel.log`
