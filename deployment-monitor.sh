#!/bin/bash

###############################################################################
# Real-time Trading Bot & OPF-CD Deployment Monitor
# Run this during deployment to ensure trading bot stays operational
###############################################################################

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration - Update these for your trading bot
TRADING_BOT_PORT=${TRADING_BOT_PORT:-8080}  # Change to your bot's port
TRADING_BOT_HEALTH_URL=${TRADING_BOT_HEALTH_URL:-"http://localhost:$TRADING_BOT_PORT"}
OPFCD_URL="http://opfcd.3bs.ltd"
OPFCD_INTERNAL_URL="http://localhost:8091"

# Create log file
LOG_FILE="/tmp/deployment-monitor-$(date +%Y%m%d_%H%M%S).log"
echo "Deployment Monitor Started: $(date)" > "$LOG_FILE"

monitor_status() {
    local timestamp=$(date '+%H:%M:%S')
    
    # Clear screen for real-time updates
    clear
    
    echo -e "${BLUE}=========================================="
    echo -e "🔍 Real-time Deployment Monitor"
    echo -e "Time: $timestamp"
    echo -e "=========================================${NC}"
    echo ""
    
    # Trading Bot Status
    echo -e "${BLUE}🤖 Trading Bot Status:${NC}"
    if curl -s --connect-timeout 3 "$TRADING_BOT_HEALTH_URL" >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Trading Bot: ONLINE ($TRADING_BOT_HEALTH_URL)${NC}"
        echo "$timestamp - Trading Bot: ONLINE" >> "$LOG_FILE"
    else
        echo -e "${RED}❌ Trading Bot: OFFLINE or UNREACHABLE${NC}"
        echo "$timestamp - Trading Bot: OFFLINE" >> "$LOG_FILE"
    fi
    
    # Check trading bot processes
    TRADING_PROCESSES=$(ps aux | grep -i trading | grep -v grep | wc -l)
    echo "Active trading processes: $TRADING_PROCESSES"
    
    # Check trading bot containers (if Docker)
    if command -v docker >/dev/null 2>&1; then
        TRADING_CONTAINERS=$(docker ps | grep -v opfcd | grep -c "Up")
        echo "Non-OPF-CD containers running: $TRADING_CONTAINERS"
    fi
    
    echo ""
    
    # OPF-CD Status
    echo -e "${BLUE}📊 OPF-CD Status:${NC}"
    
    # Check OPF-CD containers
    if [ -f "docker-compose.yml" ]; then
        echo "OPF-CD containers:"
        docker-compose ps 2>/dev/null | tail -n +3 | while read line; do
            if echo "$line" | grep -q "Up"; then
                echo -e "${GREEN}✅ $line${NC}"
            else
                echo -e "${YELLOW}⚠️  $line${NC}"
            fi
        done
    fi
    
    echo ""
    
    # Check OPF-CD connectivity
    if curl -s --connect-timeout 3 "$OPFCD_INTERNAL_URL" >/dev/null 2>&1; then
        echo -e "${GREEN}✅ OPF-CD Internal: ONLINE (port 8091)${NC}"
    else
        echo -e "${YELLOW}⚠️  OPF-CD Internal: Not yet available${NC}"
    fi
    
    if curl -s --connect-timeout 3 "$OPFCD_URL" >/dev/null 2>&1; then
        echo -e "${GREEN}✅ OPF-CD External: ONLINE ($OPFCD_URL)${NC}"
    else
        echo -e "${YELLOW}⚠️  OPF-CD External: Not yet available${NC}"
    fi
    
    echo ""
    
    # System Resources
    echo -e "${BLUE}💾 System Resources:${NC}"
    
    # Memory usage
    MEM_INFO=$(free -m | grep Mem)
    MEM_USED=$(echo $MEM_INFO | awk '{print $3}')
    MEM_TOTAL=$(echo $MEM_INFO | awk '{print $2}')
    MEM_PERCENT=$((MEM_USED * 100 / MEM_TOTAL))
    
    if [ $MEM_PERCENT -lt 80 ]; then
        echo -e "${GREEN}✅ Memory: ${MEM_USED}MB/${MEM_TOTAL}MB (${MEM_PERCENT}%)${NC}"
    else
        echo -e "${YELLOW}⚠️  Memory: ${MEM_USED}MB/${MEM_TOTAL}MB (${MEM_PERCENT}%)${NC}"
    fi
    
    # CPU Load
    LOAD=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    echo "CPU Load: $LOAD"
    
    # Disk space
    DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ $DISK_USAGE -lt 90 ]; then
        echo -e "${GREEN}✅ Disk: ${DISK_USAGE}% used${NC}"
    else
        echo -e "${YELLOW}⚠️  Disk: ${DISK_USAGE}% used${NC}"
    fi
    
    echo ""
    
    # Network Connectivity
    echo -e "${BLUE}🌐 Network Status:${NC}"
    if ping -c 1 -W 2 google.com >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Internet: Connected${NC}"
    else
        echo -e "${RED}❌ Internet: Disconnected${NC}"
    fi
    
    # Port Usage
    echo ""
    echo -e "${BLUE}🔌 Critical Ports:${NC}"
    
    PORTS_TO_CHECK=(80 443 3306 3307 6379 8080 8091 8092)
    for port in "${PORTS_TO_CHECK[@]}"; do
        if netstat -tlnp 2>/dev/null | grep -q ":$port "; then
            process=$(netstat -tlnp 2>/dev/null | grep ":$port " | awk '{print $7}' | head -1 | cut -d'/' -f2)
            if [ $port -eq $TRADING_BOT_PORT ]; then
                echo -e "${GREEN}✅ Port $port: $process (Trading Bot)${NC}"
            elif [ $port -eq 8091 ] || [ $port -eq 8092 ]; then
                echo -e "${GREEN}✅ Port $port: $process (OPF-CD)${NC}"
            else
                echo -e "${BLUE}ℹ️  Port $port: $process${NC}"
            fi
        fi
    done
    
    echo ""
    
    # Recent Events
    echo -e "${BLUE}📝 Recent Activity:${NC}"
    tail -5 "$LOG_FILE" | while read line; do
        echo "  $line"
    done
    
    echo ""
    echo -e "${YELLOW}Press Ctrl+C to stop monitoring${NC}"
    echo -e "${BLUE}Log file: $LOG_FILE${NC}"
}

# Trap Ctrl+C
trap 'echo -e "\n${GREEN}Monitoring stopped. Log saved to: $LOG_FILE${NC}"; exit 0' INT

# Main monitoring loop
echo -e "${GREEN}Starting deployment monitor...${NC}"
echo -e "${BLUE}Monitoring trading bot on: $TRADING_BOT_HEALTH_URL${NC}"
echo -e "${BLUE}Monitoring OPF-CD on: $OPFCD_URL${NC}"
echo ""

while true; do
    monitor_status
    sleep 10
done