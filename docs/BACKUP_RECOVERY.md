# Backup & Recovery Guide - OPF Capital Dashboard

This document provides comprehensive instructions for backing up and recovering the OPF Capital Dashboard database.

## Table of Contents

1. [Overview](#overview)
2. [Backup Strategy](#backup-strategy)
3. [Automated Backups](#automated-backups)
4. [Manual Backups](#manual-backups)
5. [Backup Verification](#backup-verification)
6. [Recovery Procedures](#recovery-procedures)
7. [Offsite Backup](#offsite-backup)
8. [Testing Recovery](#testing-recovery)
9. [Troubleshooting](#troubleshooting)

---

## Overview

### What Gets Backed Up

The backup system covers:
- **Database:** All tables, data, indexes, triggers, stored procedures
- **Audit Logs:** Complete audit trail (immutable)
- **User Data:** Projects, expenses, opportunities, accounts
- **Configuration:** Roles, permissions, user assignments

### What is NOT Backed Up

These should be backed up separately:
- Application code (use Git)
- Environment configuration (.env file)
- Uploaded files (if any)
- Server configuration files

### Backup Retention Policy

**Default Configuration:**
- Backups retained for **30 days**
- Stored in `backend/storage/backups/`
- Compressed with gzip (typically 90% size reduction)
- Daily automated backups at 2:00 AM

**Configurable via .env:**
```env
BACKUP_ENABLED=true
BACKUP_PATH=storage/backups
BACKUP_RETENTION_DAYS=30
```

---

## Backup Strategy

### Backup Types

#### 1. Automated Daily Backups
- **When:** Every day at 2:00 AM (configurable)
- **How:** Cron job executes backup script
- **Retention:** 30 days (configurable)
- **Location:** `backend/storage/backups/`

#### 2. Manual On-Demand Backups
- **When:** Before major changes, migrations, or updates
- **How:** Run backup script manually
- **Retention:** Not automatically deleted
- **Location:** `backend/storage/backups/`

#### 3. Pre-Deployment Backups
- **When:** Immediately before deploying code changes
- **How:** Part of deployment process
- **Retention:** Keep until deployment verified successful
- **Location:** `backend/storage/backups/`

#### 4. Safety Backups (Restore Process)
- **When:** Automatically before any restore operation
- **How:** Created by restore script
- **Retention:** Manual deletion required
- **Location:** `backend/storage/backups/pre-restore-safety_*.sql.gz`

---

## Automated Backups

### Setup Instructions

#### 1. Make Scripts Executable

```bash
chmod +x /var/www/opf-cd/backend/scripts/backup-database.sh
chmod +x /var/www/opf-cd/backend/scripts/restore-database.sh
```

#### 2. Test Backup Script

```bash
cd /var/www/opf-cd/backend/scripts
./backup-database.sh
```

**Expected output:**
```
[2026-02-14 02:00:00] =========================================
[2026-02-14 02:00:00] Starting backup process
[2026-02-14 02:00:00] =========================================
[2026-02-14 02:00:01] Starting database backup: opf_capital_dashboard
[2026-02-14 02:00:01] Backup file: /var/www/opf-cd/backend/storage/backups/opf_capital_dashboard_2026-02-14_02-00-00.sql.gz
[2026-02-14 02:00:05] Database dump successful
[2026-02-14 02:00:05] Compressing backup...
[2026-02-14 02:00:07] Backup complete: opf_capital_dashboard_2026-02-14_02-00-00.sql.gz (2.5M)
[2026-02-14 02:00:07] Verifying backup integrity...
[2026-02-14 02:00:07] Backup integrity verified
[2026-02-14 02:00:07] Cleaning backups older than 30 days
[2026-02-14 02:00:07] No old backups to delete
[2026-02-14 02:00:07] =========================================
[2026-02-14 02:00:07] Backup process completed successfully
[2026-02-14 02:00:07] =========================================
```

#### 3. Schedule with Cron

**Edit crontab:**
```bash
sudo crontab -e -u www-data
```

**Add backup schedule:**
```cron
# Daily database backup at 2:00 AM
0 2 * * * /var/www/opf-cd/backend/scripts/backup-database.sh

# Alternative schedules:
# Every 6 hours: 0 */6 * * * /var/www/opf-cd/backend/scripts/backup-database.sh
# Every hour: 0 * * * * /var/www/opf-cd/backend/scripts/backup-database.sh
# Weekly (Sunday 2 AM): 0 2 * * 0 /var/www/opf-cd/backend/scripts/backup-database.sh
```

#### 4. Monitor Backup Logs

```bash
tail -f /var/www/opf-cd/backend/storage/logs/backup-$(date +%Y-%m-%d).log
```

---

## Manual Backups

### When to Create Manual Backups

- Before deploying application updates
- Before running database migrations
- Before bulk data operations
- After significant data entry
- Before testing new features

### Creating a Manual Backup

```bash
cd /var/www/opf-cd/backend/scripts
./backup-database.sh
```

### Naming Convention

Backups are automatically named:
```
opf_capital_dashboard_YYYY-MM-DD_HH-MM-SS.sql.gz
```

Example: `opf_capital_dashboard_2026-02-14_14-30-00.sql.gz`

---

## Backup Verification

### Verify Backup Integrity

```bash
# Verify gzip compression
gzip -t /path/to/backup.sql.gz

# If successful, no output. If failed, shows error.
```

### Test Restore to Separate Database

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE opf_test;"

# Restore to test database
gunzip -c backup.sql.gz | mysql -u root -p opf_test

# Verify tables exist
mysql -u root -p opf_test -e "SHOW TABLES;"

# Drop test database
mysql -u root -p -e "DROP DATABASE opf_test;"
```

### Check Backup File Size

```bash
ls -lh /var/www/opf-cd/backend/storage/backups/

# Typical sizes (depends on data volume):
# Small deployment: 1-5 MB
# Medium deployment: 5-50 MB
# Large deployment: 50-500 MB
```

**Warning Signs:**
- Backup file size suddenly drops significantly
- Backup file size is 0 bytes
- Cannot decompress file with `gzip -t`

---

## Recovery Procedures

### Complete Database Restore

⚠️ **DANGER:** This will overwrite the current database!

#### 1. List Available Backups

```bash
cd /var/www/opf-cd/backend/scripts
./restore-database.sh
```

**Output:**
```
Available backups in /var/www/opf-cd/backend/storage/backups:
==================================================
opf_capital_dashboard_2026-02-13_02-00-00.sql.gz (2.3M) Feb 13 02:00
opf_capital_dashboard_2026-02-14_02-00-00.sql.gz (2.5M) Feb 14 02:00
==================================================

Usage: ./restore-database.sh <backup-file.sql.gz>
```

#### 2. Restore from Backup

```bash
./restore-database.sh /var/www/opf-cd/backend/storage/backups/opf_capital_dashboard_2026-02-14_02-00-00.sql.gz
```

**Process:**
1. Script validates backup file integrity
2. Creates safety backup of current database
3. Prompts for confirmation
4. Restores database from backup
5. Verifies restoration
6. Clears application cache

**Expected output:**
```
[2026-02-14 10:30:00] =========================================
[2026-02-14 10:30:00] Starting restore process
[2026-02-14 10:30:00] =========================================
[2026-02-14 10:30:01] Validating backup file integrity...
[2026-02-14 10:30:01] Backup file integrity OK
[2026-02-14 10:30:02] Creating safety backup of current database...
[2026-02-14 10:30:05] Safety backup created: pre-restore-safety_2026-02-14_10-30-00.sql.gz
[2026-02-14 10:30:05] =========================================
[2026-02-14 10:30:05] WARNING: This will overwrite the database!
[2026-02-14 10:30:05] Database: opf_capital_dashboard
[2026-02-14 10:30:05] Backup file: opf_capital_dashboard_2026-02-14_02-00-00.sql.gz
[2026-02-14 10:30:05] =========================================
Are you sure you want to continue? (yes/no): yes
[2026-02-14 10:30:10] Starting database restore...
[2026-02-14 10:30:45] Database restore completed successfully
[2026-02-14 10:30:46] Verifying restored database...
[2026-02-14 10:30:46] Table verified: users
[2026-02-14 10:30:46] Table verified: roles
[2026-02-14 10:30:46] Table verified: projects
[2026-02-14 10:30:46] Table verified: expenses
[2026-02-14 10:30:46] Table verified: opportunities
[2026-02-14 10:30:46] Database verification complete
[2026-02-14 10:30:46] =========================================
[2026-02-14 10:30:46] Restore process completed successfully
[2026-02-14 10:30:46] =========================================
[2026-02-14 10:30:47] Clearing application cache...
```

#### 3. Verify Application

After restore:

1. **Check health endpoint:**
   ```bash
   curl https://dashboard.opfcapital.com/api/health
   ```

2. **Login to application**
   - Verify you can login
   - Check dashboard loads correctly

3. **Verify data integrity**
   - Check a few projects exist
   - Verify expenses are visible
   - Check audit logs

---

## Offsite Backup

### Why Offsite Backup?

- Protection against server failure
- Protection against data center disasters
- Regulatory compliance requirements
- Long-term archival

### AWS S3 Integration (Example)

#### 1. Install AWS CLI

```bash
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
sudo ./aws/install
```

#### 2. Configure AWS Credentials

```bash
aws configure
```

#### 3. Add to .env

```env
AWS_S3_BUCKET=opf-capital-backups
AWS_DEFAULT_REGION=us-east-1
```

#### 4. Enable in Backup Script

Uncomment the offsite backup section in `backup-database.sh`:

```bash
if [ -n "${AWS_S3_BUCKET:-}" ]; then
    log "Transferring to AWS S3: ${AWS_S3_BUCKET}"
    aws s3 cp "$BACKUP_FILE_GZ" "s3://${AWS_S3_BUCKET}/backups/"
    log "Offsite backup transfer complete"
fi
```

#### 5. Test Transfer

```bash
./backup-database.sh

# Verify in S3
aws s3 ls s3://opf-capital-backups/backups/
```

---

## Testing Recovery

### Monthly Recovery Test

**Best Practice:** Test recovery monthly to ensure backups are viable.

#### Test Procedure

1. **Select a backup** (1-2 days old)
2. **Create test database** for restore
3. **Restore to test database**
4. **Verify data integrity**
5. **Document results**
6. **Clean up test database**

#### Test Script

```bash
#!/bin/bash

# Create test database
mysql -u root -p -e "CREATE DATABASE opf_recovery_test;"

# Restore latest backup to test database
LATEST_BACKUP=$(ls -t /var/www/opf-cd/backend/storage/backups/*.sql.gz | head -1)
gunzip -c "$LATEST_BACKUP" | mysql -u root -p opf_recovery_test

# Verify essential tables
TABLES=("users" "projects" "expenses" "opportunities")
for TABLE in "${TABLES[@]}"; do
    COUNT=$(mysql -u root -p opf_recovery_test -se "SELECT COUNT(*) FROM $TABLE;")
    echo "Table $TABLE: $COUNT rows"
done

# Clean up
mysql -u root -p -e "DROP DATABASE opf_recovery_test;"

echo "Recovery test complete"
```

---

## Troubleshooting

### Issue: Backup Script Fails

**Symptoms:**
```
ERROR: Database backup failed
```

**Check:**
1. Database credentials in .env
2. MySQL service is running
3. Disk space available
4. Permissions on backup directory

**Solutions:**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Check disk space
df -h

# Check permissions
ls -la /var/www/opf-cd/backend/storage/backups/
chmod 775 /var/www/opf-cd/backend/storage/backups/
```

### Issue: Backup File Corrupted

**Symptoms:**
```
ERROR: Backup file is corrupted
```

**Test:**
```bash
gzip -t /path/to/backup.sql.gz
```

**Solution:**
- Use a different backup file
- Check disk for errors: `sudo fsck`
- Ensure sufficient disk space during backup

### Issue: Restore Fails

**Symptoms:**
```
ERROR: Database restore failed
```

**Common Causes:**
1. Wrong database credentials
2. Database doesn't exist
3. Insufficient permissions
4. Corrupted backup file

**Solutions:**
```bash
# Verify database exists
mysql -u root -p -e "SHOW DATABASES;"

# If not, create it
mysql -u root -p -e "CREATE DATABASE opf_capital_dashboard;"

# Grant permissions
mysql -u root -p -e "GRANT ALL ON opf_capital_dashboard.* TO 'opf_user'@'localhost';"

# Test restore again
```

### Issue: Automated Backups Not Running

**Check cron job:**
```bash
sudo crontab -l -u www-data
```

**Check cron logs:**
```bash
grep CRON /var/log/syslog | grep backup
```

**Test script manually:**
```bash
sudo -u www-data /var/www/opf-cd/backend/scripts/backup-database.sh
```

---

## Disaster Recovery Scenarios

### Scenario 1: Accidental Data Deletion

**Steps:**
1. Identify when deletion occurred (check audit logs)
2. Select backup from before deletion
3. Create safety backup of current state
4. Restore from backup
5. Verify data recovered

### Scenario 2: Database Corruption

**Steps:**
1. Stop application (maintenance mode)
2. Attempt database repair: `mysqlcheck --repair`
3. If repair fails, restore from last good backup
4. Verify integrity
5. Resume application

### Scenario 3: Server Failure

**Steps:**
1. Provision new server
2. Deploy application code
3. Restore database from offsite backup
4. Configure environment
5. Test thoroughly before switching DNS

---

## Backup Checklist

Use this checklist monthly:

- [ ] Automated backups running daily
- [ ] Backup logs show success
- [ ] Disk space sufficient (> 20% free)
- [ ] Manual test restore successful
- [ ] Offsite backup transfer working (if configured)
- [ ] Backup retention policy being enforced
- [ ] Backup file sizes reasonable
- [ ] Safety backups being cleaned up
- [ ] Recovery documentation up to date
- [ ] Team trained on recovery procedures

---

## Contact for Backup Issues

- **Technical Support:** backup-support@opfcapital.com
- **Emergency Recovery:** emergency@opfcapital.com (24/7)

---

**Last Updated:** February 14, 2026  
**Next Review:** March 14, 2026
