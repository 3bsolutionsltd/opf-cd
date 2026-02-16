# Deployment Process & Change Management
## Developer Workflow for OPF-CD

This document outlines the deployment process and change management workflow for the OPF-CD project.

---

## 🚀 Quick Start (Daily Workflow)

```bash
# 1. Work on your feature
git checkout -b feature/your-feature-name
# Make changes...

# 2. Test locally
cd backend
php artisan test

# 3. Commit changes
git add .
git commit -m "Brief description of changes"

# 4. Push feature branch (optional - for review)
git push origin feature/your-feature-name

# 5. Merge to master
git checkout master
git merge feature/your-feature-name

# 6. Push to master - AUTO-DEPLOYS! 🚀
git push origin master

# 7. Monitor deployment
# Visit: GitHub → Actions tab → Watch deployment
```

**That's it!** Deployment happens automatically.

---

## 📋 Deployment Types

### Type 1: Feature Deployment (Most Common)

**Use Case:** New features, bug fixes, improvements

**Process:**
1. Create feature branch
2. Develop and test locally
3. Merge to master
4. Push triggers auto-deployment
5. Verify on production

**Timeline:** Instant (30-60 seconds)

---

### Type 2: Hotfix Deployment (Urgent)

**Use Case:** Critical bugs in production

**Process:**
```bash
# 1. Create hotfix branch from master
git checkout -b hotfix/critical-bug master

# 2. Make minimal fix
# Edit files...

# 3. Test quickly
php artisan test --filter=CriticalTest

# 4. Commit with clear message
git commit -m "HOTFIX: Fix critical bug in payment processing"

# 5. Merge to master
git checkout master
git merge hotfix/critical-bug

# 6. Push - deploys immediately
git push origin master

# 7. Monitor deployment closely
# Watch GitHub Actions
# Test affected functionality immediately
```

**Timeline:** 5-10 minutes total

---

### Type 3: Database Migration Deployment

**Use Case:** Changes requiring database schema updates

**Process:**
```bash
# 1. Create migration locally
cd backend
php artisan make:migration add_status_to_projects

# 2. Write migration
# Edit database/migrations/...

# 3. Test migration locally
php artisan migrate

# 4. Rollback to test reverse
php artisan migrate:rollback

# 5. Re-run migration
php artisan migrate

# 6. Commit migration file
git add database/migrations/
git commit -m "Add status column to projects table"

# 7. Push to master
git push origin master

# Deployment script automatically runs:
# php artisan migrate --force
```

**Important:**
- ✅ **Always write reversible migrations** (with `down()` method)
- ✅ Test migrations locally before pushing
- ⚠️ Avoid destructive migrations (DROP COLUMN) without backups
- ⚠️ Large data migrations should be done off-peak hours

---

### Type 4: Manual Deployment (Bypass CI/CD)

**Use Case:** Emergency situations, CI/CD is down

**Process:**
```bash
# SSH into production server
ssh your-username@your-server.com

# Navigate to deployment directory
cd /path/to/opf-cd

# Pull latest code
git pull origin master

# Run deployment script
bash deploy.sh

# Monitor output for errors
```

**Use sparingly!** Prefer automated deployments.

---

## 🔄 Branching Strategy

### Branch Types

| Branch | Purpose | Deploy To | Lifespan |
|--------|---------|-----------|----------|
| `master` | Production code | Production (auto) | Permanent |
| `feature/*` | New features | N/A | Temporary |
| `bugfix/*` | Bug fixes | N/A | Temporary |
| `hotfix/*` | Urgent fixes | Production (auto) | Temporary |

### Branch Naming Conventions

```bash
# Features
feature/add-project-dashboard
feature/export-reports

# Bug fixes
bugfix/fix-date-formatting
bugfix/correct-calculation

# Hotfixes
hotfix/payment-processor-error
hotfix/security-patch
```

---

## ✅ Pre-Deployment Checklist

Before pushing to master, ensure:

### Code Quality
- [ ] Code follows Laravel best practices
- [ ] No debug statements (`dd()`, `dump()`, `console.log()`)
- [ ] Error handling implemented
- [ ] Validation rules added for inputs

### Testing
- [ ] Tested locally with `php artisan serve`
- [ ] Unit tests pass: `php artisan test`
- [ ] Manual testing completed
- [ ] Edge cases considered

### Database
- [ ] Migration tested (up and down)
- [ ] Seeder updated if needed
- [ ] No destructive operations without backup

### Configuration
- [ ] No hardcoded values (use .env)
- [ ] New config added to `.env.example`
- [ ] No credentials in code

### Documentation
- [ ] Code comments for complex logic
- [ ] API contracts updated if endpoints changed
- [ ] README updated if setup changed

---

## 🎯 Deployment Success Criteria

### Automated Checks (GitHub Actions)

✅ Composer dependencies install successfully
✅ Tests pass (if enabled)
✅ Code pushed to server
✅ Deployment script completes

### Manual Verification (Post-Deploy)

1. **Health Check:** Visit `https://opf-cd.3bs.ltd/api/health`
   - Should return: `{"status":"healthy"}`

2. **Login Test:** Visit `https://opf-cd.3bs.ltd/login`
   - Login works without errors

3. **Feature Test:** Test changed functionality
   - New feature works as expected
   - Existing features not broken

4. **Error Logs:** Check for new errors
   ```bash
   # SSH into server
   tail -50 /path/to/opf-cd/shared/storage/logs/laravel.log
   ```

---

## ⏮️ Rollback Procedures

### When to Rollback

- Critical bug introduced
- Deployment broke core functionality
- Database migration failed partially
- Performance degradation

### Rollback Methods

#### Method 1: Instant Symlink Switch (Recommended)

```bash
# SSH into server
ssh your-username@your-server.com

# List releases
cd /path/to/opf-cd/releases
ls -lt

# Output:
# release_20260216_143022  <- Current (broken)
# release_20260216_120045  <- Previous (good)
# release_20260215_180012

# Switch to previous release
cd /path/to/opf-cd
rm current
ln -s releases/release_20260216_120045 current

# Reload services
sudo systemctl reload php-fpm 2>/dev/null || true
```

**Time to rollback:** 10 seconds
**Downtime:** Zero

#### Method 2: Rollback Migration

If database migration caused issue:

```bash
# SSH into server
cd /path/to/opf-cd/current
php artisan migrate:rollback --step=1

# Verify migration rolled back
php artisan migrate:status
```

#### Method 3: Redeploy Previous Version

```bash
# From local machine
git log --oneline -5
# Find good commit hash

# Create revert commit
git revert <bad-commit-hash>
git push origin master

# Auto-deploys previous working code
```

---

## 🔍 Monitoring & Logging

### Real-Time Deployment Monitoring

1. **GitHub Actions:** 
   - Go to repository → Actions tab
   - Watch live deployment logs
   - Get notified on failure

2. **Server Logs:**
   ```bash
   # Watch deployment live
   ssh user@server
   tail -f /path/to/opf-cd/shared/storage/logs/laravel.log
   ```

### Post-Deployment Monitoring

**First 15 minutes after deployment:**
- Monitor error logs
- Test critical paths (login, dashboards, CRUD)
- Check application health endpoint

**First hour:**
- Watch for error rate increase
- Monitor user reports
- Check performance metrics

**First 24 hours:**
- Review full error logs
- Assess deployment success
- Document any issues

---

## 📊 Deployment Metrics

Track these metrics for each deployment:

| Metric | Target | How to Check |
|--------|--------|--------------|
| **Deployment Time** | < 60 seconds | GitHub Actions logs |
| **Downtime** | 0 seconds | Zero-downtime by design |
| **Error Rate** | No increase | Laravel logs |
| **Success Rate** | 100% | GitHub Actions history |
| **Rollback Rate** | < 5% | Track rollbacks |

---

## 🚨 Incident Response

### Deploy Breaks Production

**Immediate Actions:**
1. **Rollback** (use Instant Symlink Switch)
2. **Notify team** via Slack/email
3. **Investigate** cause in logs
4. **Create hotfix** for issue
5. **Document** incident

**Timeline:**
- Rollback: 1 minute
- Investigation: 5-15 minutes
- Hotfix: 30-60 minutes
- Post-mortem: 24 hours

### Deployment Fails Mid-Process

**GitHub Actions shows failure:**
1. Check error message in Actions logs
2. Common causes:
   - SSH connection failed
   - Composer install failed
   - Migration failed
   - Permissions issue
3. Fix issue
4. Retry deployment (push again or manual trigger)

**Previous version still running** - No user impact!

---

## 👥 Team Collaboration

### Multiple Developers

**Scenario:** Two developers push to master simultaneously

**What happens:**
1. First push triggers deployment
2. Second push waits (GitHub Actions queues)
3. Deployments run sequentially
4. Both deploys succeed (if no conflicts)

**Best Practice:**
- Communicate deployments in team chat
- Use Pull Requests for review
- Avoid Friday afternoon deployments

### Code Review Process

**Recommended workflow:**
```bash
# 1. Developer creates feature branch
git checkout -b feature/new-dashboard
# ... make changes ...
git push origin feature/new-dashboard

# 2. Create Pull Request on GitHub
# Reviewer reviews code

# 3. After approval, merge via GitHub UI
# OR locally:
git checkout master
git merge feature/new-dashboard
git push origin master  # Auto-deploys
```

---

## 📅 Deployment Schedule

### Recommended Deployment Times

**Best times to deploy:**
- ✅ Monday-Thursday: 10am-4pm (business hours, team available)
- ✅ After business hours for risky changes

**Avoid deploying:**
- ❌ Friday after 3pm (limited support over weekend)
- ❌ Before major deadlines/demos
- ❌ During peak usage hours (if known)
- ❌ Right before holidays

### Deployment Cadence

| Change Type | Frequency | Timing |
|-------------|-----------|--------|
| Features | As ready | Business hours |
| Bug fixes | As needed | ASAP |
| Hotfixes | Immediate | Anytime |
| Major updates | Weekly | Tuesday AM |
| Dependencies | Monthly | Planned window |

---

## 🔒 Security Considerations

### Sensitive Changes

**Database credentials change:**
```bash
# 1. Update .env on server (manual)
ssh user@server
nano /path/to/opf-cd/shared/.env
# Update DB_PASSWORD

# 2. Push code changes
git push origin master

# 3. Verify connection
curl https://opf-cd.3bs.ltd/api/health
```

**API keys change:**
- Update in `.env` on server (never in code)
- Restart PHP-FPM if needed
- Test affected integrations

**Security patches:**
- Deploy immediately (hotfix process)
- Test quickly but thoroughly
- Monitor error logs closely

---

## 📝 Change Log

Document significant deployments:

### Example Entry:

```markdown
## [v1.2.0] - 2026-02-16

### Added
- Project status dashboard with real-time metrics
- Export functionality for reports

### Changed
- Updated payment processing logic
- Improved error messages

### Fixed
- Date formatting in expense reports
- Permission check for project deletion

### Database
- Added `status` column to `projects` table
- Modified `payments` table indexes

### Deployment Notes
- Migration adds new column (zero downtime)
- Cache cleared automatically
- No manual steps required

### Rollback Notes
- Safe to rollback via symlink switch
- Migration is reversible
```

---

## 🎓 Best Practices Summary

### DO:
✅ Test locally before pushing
✅ Write clear commit messages
✅ Monitor deployments actively
✅ Rollback quickly if issues arise
✅ Document complex changes
✅ Communicate with team

### DON'T:
❌ Push untested code
❌ Deploy critical changes on Friday
❌ Ignore deployment failures
❌ Skip migration testing
❌ Commit credentials
❌ Deploy without rollback plan

---

## 📚 Quick Reference

### Common Commands

```bash
# Check deployment history
ssh user@server
ls -lt /path/to/opf-cd/releases/

# View current release
ls -la /path/to/opf-cd/current

# Check Laravel logs
tail -100 /path/to/opf-cd/shared/storage/logs/laravel.log

# Manual deployment
cd /path/to/opf-cd
bash deploy.sh

# Rollback
cd /path/to/opf-cd
rm current
ln -s releases/release_TIMESTAMP current
```

### Important URLs

- **Production:** https://opf-cd.3bs.ltd
- **Health Check:** https://opf-cd.3bs.ltd/api/health
- **GitHub Actions:** https://github.com/3bsolutionsltd/opf-cd/actions
- **Repository:** https://github.com/3bsolutionsltd/opf-cd

---

## ✅ Deployment Checklist (Print This)

**Before Every Deployment:**
- [ ] Code tested locally
- [ ] Tests pass (`php artisan test`)
- [ ] No debug code left
- [ ] Migration tested (if applicable)
- [ ] .env.example updated (if needed)
- [ ] Team notified (if major change)

**During Deployment:**
- [ ] Watch GitHub Actions logs
- [ ] Monitor server logs (if available)

**After Deployment:**
- [ ] Visit health check endpoint
- [ ] Test changed functionality
- [ ] Check error logs (first 15 mins)
- [ ] Confirm with stakeholders (if major)

**If Issues:**
- [ ] Rollback immediately
- [ ] Investigate cause
- [ ] Create hotfix
- [ ] Document incident

---

**Questions?** Check [CICD_SETUP.md](CICD_SETUP.md) for technical setup details.
