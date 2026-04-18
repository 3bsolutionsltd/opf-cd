#!/bin/bash

###############################################################################
# Pre-Deployment Safety Check for Trading Bot Protection
# Run this BEFORE deploying OPF-CD to ensure no conflicts
###############################################################################

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo -e "🛡️  Trading Bot Safety Check"
echo -e "=========================================="
echo -e "Checking for potential conflicts before OPF-CD deployment${NC}"
echo ""

# Check what's currently running
echo -e "${BLUE}📊 Current System Status:${NC}"
echo "Active processes:"
ps aux | grep -E "(python|node|java|trading|bot)" | grep -v grep | head -10

echo ""
echo "Docker containers (if any):"
if command -v docker &> /dev/null; then
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "No containers running"
else
    echo "Docker not installed"
fi

echo ""

# Check ports that OPF-CD will use
echo -e "${BLUE}🔌 Port Conflict Check:${NC}"
echo "Checking OPF-CD ports for conflicts..."

OPFCD_PORTS=(8091 8092 3307 6379)
CONFLICTS=false

for port in "${OPFCD_PORTS[@]}"; do
    if netstat -tlnp 2>/dev/null | grep -q ":$port "; then
        process=$(netstat -tlnp 2>/dev/null | grep ":$port " | awk '{print $7}' | head -1)
        echo -e "${RED}❌ CONFLICT: Port $port occupied by $process${NC}"
        CONFLICTS=true
    else
        echo -e "${GREEN}✅ Port $port: Available${NC}"
    fi
done

echo ""

# Check common trading bot ports to ensure they're protected
echo -e "${BLUE}🤖 Trading Bot Protection Check:${NC}"
echo "Checking common trading bot ports..."

COMMON_TRADING_PORTS=(3000 3001 5000 8000 8080 8888 9000)
TRADING_ACTIVE=false

for port in "${COMMON_TRADING_PORTS[@]}"; do
    if netstat -tlnp 2>/dev/null | grep -q ":$port "; then
        process=$(netstat -tlnp 2>/dev/null | grep ":$port " | awk '{print $7}' | head -1)
        echo -e "${YELLOW}⚠️  Port $port: In use by $process (likely trading bot)${NC}"
        TRADING_ACTIVE=true
    fi
done

if [ "$TRADING_ACTIVE" = false ]; then
    echo -e "${GREEN}✅ No trading bot ports detected${NC}"
fi

echo ""

# Check system resources
echo -e "${BLUE}💾 Resource Usage:${NC}"
echo "Memory usage:"
free -h | grep Mem | awk '{printf "Used: %s/%s (%.1f%%)\n", $3, $2, ($3/$2)*100}'

echo ""
echo "CPU load:"
uptime | awk '{print "Load average:", $(NF-2), $(NF-1), $NF}'

echo ""
echo "Disk usage:"
df -h / | tail -1 | awk '{printf "Used: %s/%s (%s)\n", $3, $2, $5}'

echo ""

# Network connectivity
echo -e "${BLUE}🌐 Network Check:${NC}"
if ping -c 1 google.com &> /dev/null; then
    echo -e "${GREEN}✅ Internet connectivity: OK${NC}"
else
    echo -e "${RED}❌ Internet connectivity: FAILED${NC}"
fi

# Check if main web server is running
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx: Running${NC}"
    echo "Active sites:"
    nginx -T 2>/dev/null | grep server_name | head -5 || echo "Unable to read nginx config"
elif systemctl is-active --quiet apache2; then
    echo -e "${GREEN}✅ Apache: Running${NC}"
else
    echo -e "${YELLOW}⚠️  No web server detected${NC}"
fi

echo ""

# Final assessment
echo -e "${BLUE}=========================================="
echo -e "📋 Safety Assessment"
echo -e "==========================================${NC}"

if [ "$CONFLICTS" = true ]; then
    echo -e "${RED}❌ DEPLOYMENT BLOCKED: Port conflicts detected${NC}"
    echo ""
    echo -e "${YELLOW}Resolution Required:${NC}"
    echo "1. Stop services using conflicting ports, OR"
    echo "2. Modify docker-compose.yml to use different ports"
    echo ""
    echo "Suggested port changes for docker-compose.yml:"
    echo "  opfcd-nginx: '8191:80' and '8192:443'"  
    echo "  opfcd-database: '3308:3306'"
    echo ""
    exit 1
else
    echo -e "${GREEN}✅ SAFE TO DEPLOY: No conflicts detected${NC}"
    echo ""
    echo -e "${BLUE}Deployment will use:${NC}"
    echo "• Ports 8091, 8092 (HTTP/HTTPS)"
    echo "• Port 3307 (MySQL external access)"
    echo "• Internal Docker network (172.20.0.0/24)"
    echo "• Separate data volumes"
    echo ""
    echo -e "${GREEN}Trading bot operations will not be affected${NC}"
fi

echo ""
echo -e "${BLUE}Next step: Run deployment script${NC}"
echo "Command: ./deploy-vps.sh"