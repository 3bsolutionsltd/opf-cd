# 🚀 OPF-CD VPS Deployment Guide

Complete guide to deploy OPF-CD to your Contabo VPS alongside your existing trading bot.

## 📋 **Pre-Deployment Checklist**

### 1. **VPS Access & Domain Setup**
- [ ] SSH access to Contabo VPS (root or sudo user)
- [ ] Domain `opfcd.3bs.ltd` DNS pointed to your VPS IP
- [ ] Docker and Docker Compose installed on VPS

### 2. **Check Current VPS Setup**
Run this on your VPS to check the current environment:

```bash
# Check what's currently running
docker ps

# Check available ports
netstat -tlnp

# Check nginx configuration (if running)
nginx -t && nginx -s reload

# Check system resources
df -h && free -m
```

---

## 🐳 **Docker Deployment Steps**

### **Step 1: Upload Project Files**

Option A - Git Clone (Recommended):
```bash
# On your VPS
cd /opt
git clone https://github.com/3bsolutionsltd/opf-cd.git
cd opf-cd
git checkout copilot/implement-lead-qualification
```

Option B - SCP Upload:
```bash
# From your local machine
scp -r C:\Users\DELL\opf-cd root@your-vps-ip:/opt/opf-cd
```

### **Step 2: Configure Environment**
```bash
cd /opt/opf-cd

# Copy and edit environment file
cp docker.env.example .env
nano .env

# Update these critical values:
# - DB_PASSWORD=your_secure_password_here
# - DB_ROOT_PASSWORD=your_root_password_here  
# - REDIS_PASSWORD=your_redis_password_here
# - SSL_EMAIL=your-email@domain.com
```

### **Step 3: Generate Laravel App Key**
```bash
# Generate application key
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"

# Add the generated key to backend_old_manual_deployment/.env
```

### **Step 4: Deploy with Docker**
```bash
# Make deployment script executable
chmod +x deploy-vps.sh

# Run deployment
./deploy-vps.sh
```

---

## 🔧 **Integration with Existing Trading Bot**

### **Port Configuration**
Your OPF-CD will use these ports:
- `8091` - HTTP access (internal)
- `8092` - HTTPS access (internal)  
- `3307` - MySQL access (external)

These ports should not conflict with your trading bot.

### **Reverse Proxy Setup**
Add this to your main nginx configuration:

```nginx
# /etc/nginx/sites-available/opfcd
upstream opfcd_backend {
    server 127.0.0.1:8091;
}

server {
    listen 80;
    server_name opfcd.3bs.ltd;

    location / {
        proxy_pass http://opfcd_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable the site:
```bash
ln -s /etc/nginx/sites-available/opfcd /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 🔒 **SSL Certificate Setup**

### **Install Certbot**
```bash
apt update
apt install certbot python3-certbot-nginx
```

### **Get Certificate**
```bash
certbot --nginx -d opfcd.3bs.ltd
```

### **Auto-renewal**
```bash
# Test renewal
certbot renew --dry-run

# Crontab entry (auto-added by certbot)
# 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 📊 **Monitoring & Management**

### **Container Management**
```bash
cd /opt/opf-cd

# View running containers
docker-compose ps

# View logs
docker-compose logs -f opfcd-app
docker-compose logs -f opfcd-nginx
docker-compose logs -f opfcd-database

# Restart services
docker-compose restart

# Stop services
docker-compose down

# Update application
git pull origin copilot/implement-lead-qualification
docker-compose build --no-cache
docker-compose up -d
```

### **Database Management**
```bash
# Connect to database
docker-compose exec opfcd-database mysql -u opfcd_user -p opfcd

# Backup database
docker-compose exec opfcd-database mysqldump -u opfcd_user -p opfcd > backup.sql

# Restore database
docker-compose exec -T opfcd-database mysql -u opfcd_user -p opfcd < backup.sql
```

### **Application Management**
```bash
# Run artisan commands
docker-compose exec opfcd-app php artisan migrate
docker-compose exec opfcd-app php artisan cache:clear
docker-compose exec opfcd-app php artisan queue:work

# View application logs
docker-compose exec opfcd-app tail -f storage/logs/laravel.log
```

---

## 🚨 **Troubleshooting**

### **Common Issues**

**Port Conflicts:**
```bash
# Check what's using ports
netstat -tlnp | grep :8091
netstat -tlnp | grep :3307

# Change ports in docker-compose.yml if needed
```

**Database Connection Issues:**
```bash
# Check database container
docker-compose logs opfcd-database

# Test connection
docker-compose exec opfcd-app php artisan tinker
# Try: DB::connection()->getPdo();
```

**SSL Issues:**
```bash
# Check certificate status
certbot certificates

# Renew certificate
certbot renew --nginx
```

**Application Errors:**
```bash
# Check PHP logs
docker-compose logs opfcd-app

# Check Laravel logs
docker-compose exec opfcd-app tail -f storage/logs/laravel.log

# Clear caches
docker-compose exec opfcd-app php artisan cache:clear
docker-compose exec opfcd-app php artisan config:clear
```

---

## 📈 **Performance Optimization**

### **Resource Limits**
Edit `docker-compose.yml` to add resource limits:
```yaml
services:
  opfcd-app:
    deploy:
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 256M
```

### **Database Optimization**
```sql
-- Connect to MySQL and run
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
```

### **Nginx Optimization**
Update `docker/nginx/nginx.conf`:
```nginx
worker_processes auto;
worker_connections 2048;
```

---

## 🔄 **Automated Backups**

Create backup script `/opt/opf-cd/backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/opt/opf-cd/backups"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Database backup
docker-compose exec -T opfcd-database mysqldump -u opfcd_user -p$DB_PASSWORD opfcd > "$BACKUP_DIR/db_$DATE.sql"

# Application files backup (if needed)
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" backend_old_manual_deployment/storage

# Cleanup old backups (keep 7 days)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Add to cron:
```bash
# Daily backup at 2 AM
0 2 * * * /opt/opf-cd/backup.sh
```

---

## 🎯 **Final Verification**

After deployment, verify these work:
- [ ] `http://opfcd.3bs.ltd` loads the application
- [ ] `https://opfcd.3bs.ltd` works with SSL
- [ ] Database connections work
- [ ] Trading bot still functions normally
- [ ] All containers are running: `docker-compose ps`

**🎉 Your OPF-CD is now running alongside your trading bot!**