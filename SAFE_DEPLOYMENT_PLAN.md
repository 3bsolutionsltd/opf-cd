# 🚀 Non-Disruptive Deployment to Contabo VPS
## Safe OPF-CD Deployment While Trading Bot Operates

**CRITICAL**: This deployment is designed to run alongside your trading bot without interference.

---

## 🛡️ **Step 1: Pre-Deployment Safety Check**

**On your Contabo VPS**, run the safety checker first:

```bash
# Upload and run safety check
wget https://raw.githubusercontent.com/3bsolutionsltd/opf-cd/copilot/implement-lead-qualification/trading-bot-safety-check.sh
chmod +x trading-bot-safety-check.sh
./trading-bot-safety-check.sh
```

**⚠️ DO NOT PROCEED** until you get "✅ SAFE TO DEPLOY" message.

---

## 🔒 **Step 2: Trading Bot Protection**

Before deployment, let's ensure your trading bot is protected:

```bash
# 1. Check current trading bot status
docker ps  # if containerized
ps aux | grep -i trading  # if running as process

# 2. Note which ports your trading bot uses
netstat -tlnp | grep LISTEN

# 3. Create backup of current nginx config (if applicable)
cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup.$(date +%Y%m%d)

# 4. Test trading bot connectivity
curl -I http://localhost:8080  # adjust port as needed
```

---

## 📦 **Step 3: Deploy OPF-CD in Isolation**

### **3.1 Clone Project**
```bash
# Create separate directory
mkdir -p /opt/applications
cd /opt/applications

# Clone OPF-CD
git clone https://github.com/3bsolutionsltd/opf-cd.git
cd opf-cd
git checkout copilot/implement-lead-qualification
```

### **3.2 Configure Environment**
```bash
# Copy environment template
cp docker.env.example .env

# Generate secure passwords
echo "DB_PASSWORD=$(openssl rand -base64 32)" >> .env
echo "DB_ROOT_PASSWORD=$(openssl rand -base64 32)" >> .env  
echo "REDIS_PASSWORD=$(openssl rand -base64 32)" >> .env

# Edit remaining settings
nano .env
```

**Required changes in `.env`:**
```env
DOMAIN=opfcd.3bs.ltd
SSL_EMAIL=your-email@3bs.ltd
```

### **3.3 Generate Laravel Key**
```bash
# Create Laravel environment
cd backend_old_manual_deployment
cp .env.example .env

# Update Laravel .env
cat > .env << 'EOF'
APP_NAME="OPF-CD"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=https://opfcd.3bs.ltd

DB_CONNECTION=mysql
DB_HOST=opfcd-database
DB_PORT=3306
DB_DATABASE=opfcd
DB_USERNAME=opfcd_user
DB_PASSWORD=your_db_password_here

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=opfcd-redis
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379
EOF

# Replace passwords with actual values from main .env
cd ..
```

---

## 🚀 **Step 4: Safe Deployment**

### **4.1 Test Docker Setup**
```bash
# Verify Docker Compose configuration
docker-compose config

# Check for port conflicts one more time
./trading-bot-safety-check.sh
```

### **4.2 Deploy in Stages**

**Stage 1: Database & Redis Only**
```bash
# Start only supporting services first
docker-compose up -d opfcd-database opfcd-redis

# Wait and verify
sleep 15
docker-compose ps
docker-compose logs opfcd-database
```

**Stage 2: Application**
```bash
# Start application
docker-compose up -d opfcd-app

# Run migrations
docker-compose exec opfcd-app php artisan migrate --force
docker-compose exec opfcd-app php artisan db:seed --force

# Verify application
docker-compose logs opfcd-app
```

**Stage 3: Web Server & Workers**
```bash
# Start remaining services
docker-compose up -d

# Final verification
docker-compose ps
```

### **4.3 Verify Trading Bot Still Active**
```bash
# Check trading bot is still running
docker ps | grep -v opfcd  # should show your trading bot
ps aux | grep -i trading   # if not containerized

# Test trading bot endpoints
curl -I http://localhost:8080  # adjust to your bot's port
```

---

## 🌐 **Step 5: Configure Reverse Proxy**

### **5.1 Safe Nginx Configuration**
```bash
# Create OPF-CD specific config
cat > /etc/nginx/sites-available/opfcd << 'EOF'
# OPF-CD Reverse Proxy - Isolated Configuration
upstream opfcd_backend {
    server 127.0.0.1:8091;
}

server {
    listen 80;
    server_name opfcd.3bs.ltd;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://opfcd_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }
}
EOF

# Enable site
ln -s /etc/nginx/sites-available/opfcd /etc/nginx/sites-enabled/

# Test nginx configuration (CRITICAL)
nginx -t

# If test passes, reload (not restart to avoid trading bot disruption)
nginx -s reload
```

### **5.2 Verify Trading Bot Still Works**
```bash
# Test your trading bot endpoints again
curl -I http://your-trading-bot-domain.com
curl -I http://localhost:trading-bot-port

# Check nginx error logs for issues
tail -f /var/log/nginx/error.log &

# Test OPF-CD
curl -I http://opfcd.3bs.ltd
```

---

## 🔒 **Step 6: SSL Certificate (Non-Disruptive)**

```bash
# Install certbot if not present
apt update && apt install certbot python3-certbot-nginx

# Get certificate for OPF-CD only
certbot --nginx -d opfcd.3bs.ltd --non-interactive --agree-tos --email your-email@3bs.ltd

# Verify both sites work
curl -I https://opfcd.3bs.ltd
curl -I http://your-trading-bot-domain.com  # Should still work
```

---

## 📊 **Step 7: Monitoring & Verification**

### **7.1 Health Checks**
```bash
# OPF-CD health check
curl -f http://opfcd.3bs.ltd/health || echo "OPF-CD health check failed"

# Trading bot health check  
curl -f http://localhost:8080/status || echo "Trading bot check failed"

# Container status
docker-compose ps
```

### **7.2 Resource Monitoring**
```bash
# Check system resources
htop  # or top

# Monitor logs
docker-compose logs -f --tail=50 opfcd-app &
tail -f /var/log/nginx/access.log &
```

---

## 🚨 **Emergency Rollback Plan**

If anything goes wrong:

```bash
# Stop OPF-CD immediately
cd /opt/applications/opf-cd
docker-compose down

# Remove nginx config
rm /etc/nginx/sites-enabled/opfcd
nginx -s reload

# Verify trading bot is unaffected
curl -I http://localhost:trading-bot-port
docker ps | grep -v opfcd
```

---

## ✅ **Success Verification Checklist**

After deployment, verify:

- [ ] Trading bot still accessible and functioning
- [ ] Trading bot containers/processes running normally  
- [ ] OPF-CD accessible at https://opfcd.3bs.ltd
- [ ] All OPF-CD containers running: `docker-compose ps`
- [ ] Database migrations completed
- [ ] SSL certificate active
- [ ] No port conflicts
- [ ] System resources stable
- [ ] Both applications logging normally

---

## 📞 **Support Commands**

```bash
# Check everything is running
docker ps && systemctl status nginx

# View all logs
docker-compose logs -f

# Monitor system resources
watch 'free -h && df -h && docker stats --no-stream'

# Test both applications
curl -I https://opfcd.3bs.ltd && curl -I http://localhost:trading-bot-port
```

**🎯 This deployment strategy ensures your trading bot continues operating normally while OPF-CD runs in complete isolation.**