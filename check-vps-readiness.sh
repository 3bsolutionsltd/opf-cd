#!/bin/bash

###############################################################################
# VPS Environment Check for OPF-CD Deployment
# Run this script on your Contabo VPS to check readiness
###############################################################################

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo -e "🔍 VPS Environment Check for OPF-CD"
echo -e "==========================================${NC}"
echo ""

# Check OS
echo -e "${BLUE}📋 System Information:${NC}"
echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d'=' -f2 | tr -d '\"')"
echo "Kernel: $(uname -r)"
echo "Architecture: $(uname -m)"
echo "Uptime: $(uptime -p)"
echo ""

# Check resources
echo -e "${BLUE}💾 System Resources:${NC}"
echo "Memory:"
free -h
echo ""
echo "Disk Space:"
df -h /
echo ""

# Check Docker
echo -e "${BLUE}🐳 Docker Status:${NC}"
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✅ Docker installed:${NC} $(docker --version)"
    
    if systemctl is-active --quiet docker; then
        echo -e "${GREEN}✅ Docker service running${NC}"
    else
        echo -e "${RED}❌ Docker service not running${NC}"
    fi
    
    echo "Running containers:"
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
else
    echo -e "${RED}❌ Docker not installed${NC}"
fi
echo ""

# Check Docker Compose
echo -e "${BLUE}📦 Docker Compose Status:${NC}"
if command -v docker-compose &> /dev/null; then
    echo -e "${GREEN}✅ Docker Compose installed:${NC} $(docker-compose --version)"
elif docker compose version &> /dev/null; then
    echo -e "${GREEN}✅ Docker Compose (plugin) installed:${NC} $(docker compose version)"
else
    echo -e "${RED}❌ Docker Compose not installed${NC}"
fi
echo ""

# Check ports
echo -e "${BLUE}🔌 Port Usage:${NC}"
echo "Checking critical ports for OPF-CD deployment..."

check_port() {
    local port=$1
    local service=$2
    if netstat -tlnp 2>/dev/null | grep -q ":$port "; then
        local process=$(netstat -tlnp 2>/dev/null | grep ":$port " | awk '{print $7}' | head -1)
        echo -e "${YELLOW}⚠️  Port $port ($service): OCCUPIED by $process${NC}"
    else
        echo -e "${GREEN}✅ Port $port ($service): Available${NC}"
    fi
}

check_port "80" "HTTP"
check_port "443" "HTTPS" 
check_port "3306" "MySQL Default"
check_port "3307" "OPF-CD MySQL"
check_port "6379" "Redis Default"
check_port "8091" "OPF-CD HTTP"
check_port "8092" "OPF-CD HTTPS"
echo ""

# Check web server
echo -e "${BLUE}🌐 Web Server Status:${NC}"
if command -v nginx &> /dev/null; then
    echo -e "${GREEN}✅ Nginx installed:${NC} $(nginx -v 2>&1 | cut -d'/' -f2)"
    
    if systemctl is-active --quiet nginx; then
        echo -e "${GREEN}✅ Nginx service running${NC}"
        
        # Check nginx configuration
        if nginx -t &> /dev/null; then
            echo -e "${GREEN}✅ Nginx configuration valid${NC}"
        else
            echo -e "${RED}❌ Nginx configuration has errors${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Nginx service not running${NC}"
    fi
elif command -v apache2 &> /dev/null; then
    echo -e "${GREEN}✅ Apache installed:${NC} $(apache2 -v | head -1 | cut -d'/' -f2 | cut -d' ' -f1)"
    
    if systemctl is-active --quiet apache2; then
        echo -e "${GREEN}✅ Apache service running${NC}"
    else
        echo -e "${YELLOW}⚠️  Apache service not running${NC}"
    fi
else
    echo -e "${RED}❌ No web server (Nginx/Apache) detected${NC}"
fi
echo ""

# Check SSL capability
echo -e "${BLUE}🔒 SSL/TLS Status:${NC}"
if command -v certbot &> /dev/null; then
    echo -e "${GREEN}✅ Certbot installed:${NC} $(certbot --version 2>&1 | head -1)"
    
    echo "Existing certificates:"
    if certbot certificates 2>/dev/null | grep -q "Certificate Name"; then
        certbot certificates 2>/dev/null | grep -E "(Certificate Name|Domains|Expiry Date)" | head -9
    else
        echo "No certificates found"
    fi
else
    echo -e "${YELLOW}⚠️  Certbot not installed (needed for SSL)${NC}"
fi
echo ""

# Check database
echo -e "${BLUE}🗄️  Database Status:${NC}"
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✅ MySQL client installed${NC}"
else
    echo -e "${YELLOW}⚠️  MySQL client not installed${NC}"
fi

# Check if MySQL server is running (outside Docker)
if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
    echo -e "${GREEN}✅ MySQL/MariaDB service running on host${NC}"
else
    echo -e "${BLUE}ℹ️  No MySQL service running on host (will use Docker)${NC}"
fi
echo ""

# Check network
echo -e "${BLUE}🌍 Network Status:${NC}"
if ping -c 1 google.com &> /dev/null; then
    echo -e "${GREEN}✅ Internet connectivity working${NC}"
else
    echo -e "${RED}❌ No internet connectivity${NC}"
fi

# Get public IP
PUBLIC_IP=$(curl -s -4 icanhazip.com 2>/dev/null || echo "Unable to detect")
echo "Public IP: $PUBLIC_IP"
echo ""

# Check firewall
echo -e "${BLUE}🛡️  Firewall Status:${NC}"
if command -v ufw &> /dev/null; then
    if ufw status | grep -q "Status: active"; then
        echo -e "${GREEN}✅ UFW firewall active${NC}"
        echo "UFW Rules:"
        ufw status numbered | head -10
    else
        echo -e "${YELLOW}⚠️  UFW firewall inactive${NC}"
    fi
elif command -v iptables &> /dev/null; then
    echo -e "${BLUE}ℹ️  Using iptables firewall${NC}"
    echo "Active rules: $(iptables -L | grep -c '^ACCEPT\|^DROP\|^REJECT')"
else
    echo -e "${YELLOW}⚠️  No firewall detected${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}=========================================="
echo -e "📊 Deployment Readiness Summary"
echo -e "==========================================${NC}"

# Check critical requirements
READY=true

if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker installation required${NC}"
    READY=false
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${RED}❌ Docker Compose installation required${NC}"
    READY=false
fi

if ! command -v nginx &> /dev/null && ! command -v apache2 &> /dev/null; then
    echo -e "${YELLOW}⚠️  Web server recommended for reverse proxy${NC}"
fi

if ! command -v certbot &> /dev/null; then
    echo -e "${YELLOW}⚠️  Certbot recommended for SSL certificates${NC}"
fi

# Port conflicts
if netstat -tlnp 2>/dev/null | grep -q ":8091 \|:8092 \|:3307 "; then
    echo -e "${YELLOW}⚠️  Some OPF-CD ports are occupied - may need adjustment${NC}"
fi

if [ "$READY" = true ]; then
    echo -e "${GREEN}🎉 VPS is ready for OPF-CD deployment!${NC}"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo "1. Clone/upload OPF-CD project files"
    echo "2. Configure environment variables" 
    echo "3. Run deployment script: ./deploy-vps.sh"
    echo "4. Configure reverse proxy and SSL"
else
    echo -e "${RED}⚠️  VPS needs setup before deployment${NC}"
    echo ""
    echo -e "${BLUE}Required installations:${NC}"
    
    if ! command -v docker &> /dev/null; then
        echo "• Install Docker: curl -fsSL https://get.docker.com -o get-docker.sh && sh get-docker.sh"
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        echo "• Install Docker Compose: apt install docker-compose"
    fi
    
    echo "• Install Certbot: apt install certbot python3-certbot-nginx"
fi

echo ""
echo -e "${BLUE}For detailed deployment instructions, see: VPS_DEPLOYMENT_GUIDE.md${NC}"