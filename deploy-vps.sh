#!/bin/bash

###############################################################################
# OPF-CD VPS Deployment Script
# Deploy OPF-CD to Contabo VPS using Docker
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="OPF-CD"
DOMAIN="opfcd.3bs.ltd"
COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env"

###############################################################################
# Helper Functions
###############################################################################

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

###############################################################################
# Pre-deployment Checks
###############################################################################

log_info "=========================================="
log_info "🚀 Starting $APP_NAME VPS Deployment"
log_info "=========================================="
log_info "Domain: $DOMAIN"
log_info "Timestamp: $(date)"
log_info "=========================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    log_error "Docker is not installed. Please install Docker first."
    exit 1
fi
log_success "Docker: $(docker --version)"

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    log_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi
log_success "Docker Compose: $(docker-compose --version)"

# Check if compose file exists
if [ ! -f "$COMPOSE_FILE" ]; then
    log_error "Docker Compose file ($COMPOSE_FILE) not found!"
    exit 1
fi

###############################################################################
# Environment Setup
###############################################################################

log_info "Setting up environment..."

# Create .env from example if it doesn't exist
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "docker.env.example" ]; then
        cp docker.env.example "$ENV_FILE"
        log_warning "Created $ENV_FILE from example. Please update the passwords!"
        log_warning "Edit $ENV_FILE and update:"
        echo "  - DB_PASSWORD"
        echo "  - DB_ROOT_PASSWORD" 
        echo "  - REDIS_PASSWORD"
        echo "  - APP_KEY (run: php artisan key:generate --show)"
        echo ""
        read -p "Press Enter after updating $ENV_FILE..."
    else
        log_error ".env file not found and no example file available!"
        exit 1
    fi
fi

# Create necessary directories
mkdir -p storage/logs/nginx
mkdir -p database/backups
mkdir -p docker/nginx/ssl

log_success "Environment setup complete"

###############################################################################
# Laravel Application Setup
###############################################################################

log_info "Preparing Laravel application..."

# Copy backend files if needed
if [ -d "backend_old_manual_deployment" ] && [ ! -f "backend_old_manual_deployment/.env" ]; then
    log_info "Setting up Laravel environment..."
    
    # Create production .env for Laravel
    cat > backend_old_manual_deployment/.env << EOL
APP_NAME="OPF-CD"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=https://$DOMAIN

DB_CONNECTION=mysql
DB_HOST=opfcd-database
DB_PORT=3306
DB_DATABASE=opfcd
DB_USERNAME=opfcd_user
DB_PASSWORD=$(grep DB_PASSWORD $ENV_FILE | cut -d '=' -f2)

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=opfcd-redis
REDIS_PASSWORD=$(grep REDIS_PASSWORD $ENV_FILE | cut -d '=' -f2)
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@$DOMAIN"
MAIL_FROM_NAME="OPF-CD"

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error
EOL
    
    log_success "Laravel .env created"
fi

###############################################################################
# Docker Deployment
###############################################################################

log_info "Building and starting Docker containers..."

# Pull latest images
docker-compose pull

# Build application image
docker-compose build --no-cache

# Start services
docker-compose up -d

log_success "Docker containers started"

###############################################################################
# Database Migration
###############################################################################

log_info "Running database migrations..."

# Wait for database to be ready
sleep 10

# Run migrations
docker-compose exec -T opfcd-app php artisan migrate --force

# Run seeders (if any)
if docker-compose exec -T opfcd-app php artisan db:seed --class=DatabaseSeeder; then
    log_success "Database seeded"
else
    log_warning "No seeders found or seeding failed"
fi

# Clear and cache configurations
docker-compose exec -T opfcd-app php artisan config:cache
docker-compose exec -T opfcd-app php artisan route:cache
docker-compose exec -T opfcd-app php artisan view:cache

log_success "Database setup complete"

###############################################################################
# Reverse Proxy Setup
###############################################################################

log_info "Setting up reverse proxy..."

# Create nginx reverse proxy config for main server
cat > /tmp/opfcd-proxy.conf << EOL
# OPF-CD Reverse Proxy Configuration
# Add this to your main nginx configuration

upstream opfcd_backend {
    server 127.0.0.1:8091;  # HTTP
    # server 127.0.0.1:8092;  # HTTPS (after SSL setup)
}

server {
    listen 80;
    server_name $DOMAIN;

    location / {
        proxy_pass http://opfcd_backend;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Increase timeout for long-running requests
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }
}
EOL

log_success "Reverse proxy configuration created at /tmp/opfcd-proxy.conf"
log_warning "Please add this configuration to your main nginx setup"

###############################################################################
# SSL Setup Instructions
###############################################################################

log_info "SSL Certificate Setup..."

cat << EOL

📋 SSL Setup Instructions:
==========================

1. Install Certbot (if not already installed):
   apt update && apt install certbot python3-certbot-nginx

2. Get SSL certificate:
   certbot --nginx -d $DOMAIN

3. Update nginx configuration:
   - Edit your main nginx config
   - Add the reverse proxy configuration from /tmp/opfcd-proxy.conf
   - Restart nginx: systemctl restart nginx

4. Update Docker nginx for HTTPS:
   - Uncomment SSL lines in docker/nginx/sites/opfcd.conf
   - Copy SSL certificates to docker/nginx/ssl/
   - Restart containers: docker-compose restart

EOL

###############################################################################
# Final Status
###############################################################################

log_info "Checking service status..."

# Check container status
docker-compose ps

log_info "=========================================="
log_success "🎉 $APP_NAME Deployment Complete!"
log_info "=========================================="

cat << EOL

📊 Service Information:
======================
🌐 Domain: $DOMAIN
🐳 Containers: $(docker-compose ps --services | wc -l) services
💾 Database: MySQL on port 3307 (external)
🔄 Redis: Available internally
📱 Application: http://localhost:8091 (internal)

📋 Next Steps:
==============
1. Configure your main nginx reverse proxy
2. Set up SSL certificates with Let's Encrypt
3. Update DNS records to point $DOMAIN to your VPS IP
4. Test the application: http://$DOMAIN

🔧 Management Commands:
======================
View logs:        docker-compose logs -f
Restart services: docker-compose restart
Stop services:    docker-compose down
Update app:       git pull && docker-compose build && docker-compose up -d

🚨 Important:
============
- Update all passwords in .env file
- Configure regular database backups
- Monitor logs regularly
- Keep Docker images updated

EOL

log_info "Deployment script completed successfully!"