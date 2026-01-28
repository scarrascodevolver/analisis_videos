#!/bin/bash

###############################################################################
# Queue Worker Control Script
# Manages video compression queue worker based on time of day
#
# Usage:
#   ./queue-control.sh start    - Start queue worker
#   ./queue-control.sh stop     - Stop queue worker
#   ./queue-control.sh status   - Show queue worker status
#   ./queue-control.sh auto     - Auto start/stop based on time (for cron)
###############################################################################

WORKER_NAME="rugby-queue-worker:*"
WORK_START_HOUR=8   # 8 AM
WORK_END_HOUR=22    # 10 PM

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to start queue worker
start_worker() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Starting queue worker..."

    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl start $WORKER_NAME
        echo -e "${GREEN}✓${NC} Queue worker started (compression enabled)"
    else
        echo -e "${RED}✗${NC} Supervisor not found. Starting manually..."
        cd "$(dirname "$0")"
        nohup php artisan queue:work database --sleep=3 --tries=1 --timeout=14400 > storage/logs/queue.log 2>&1 &
        echo -e "${GREEN}✓${NC} Queue worker started in background (PID: $!)"
    fi
}

# Function to stop queue worker
stop_worker() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Stopping queue worker..."

    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl stop $WORKER_NAME
        echo -e "${GREEN}✓${NC} Queue worker stopped (compression paused)"
    else
        echo -e "${RED}✗${NC} Supervisor not found. Killing manual processes..."
        pkill -f "queue:work"
        echo -e "${GREEN}✓${NC} Queue worker processes killed"
    fi
}

# Function to show status
show_status() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Queue Worker Status:"
    echo ""

    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl status $WORKER_NAME
    else
        echo "Checking manual processes..."
        ps aux | grep "queue:work" | grep -v grep || echo "No queue worker processes running"
    fi

    echo ""
    echo "Current hour: $(date +%H)"
    echo "Work hours: ${WORK_START_HOUR}:00 - ${WORK_END_HOUR}:00"
}

# Function to auto start/stop based on time (for cron)
auto_control() {
    CURRENT_HOUR=$(date +%H)
    CURRENT_HOUR=$((10#$CURRENT_HOUR))  # Convert to decimal

    # Check if we're in work hours (8 AM - 10 PM)
    if [ $CURRENT_HOUR -ge $WORK_START_HOUR ] && [ $CURRENT_HOUR -lt $WORK_END_HOUR ]; then
        echo -e "${YELLOW}[AUTO]${NC} Work hours detected (${CURRENT_HOUR}:00). Ensuring worker is STOPPED..."
        stop_worker
    else
        echo -e "${GREEN}[AUTO]${NC} Off hours detected (${CURRENT_HOUR}:00). Ensuring worker is STARTED..."
        start_worker
    fi
}

# Main script
case "$1" in
    start)
        start_worker
        ;;
    stop)
        stop_worker
        ;;
    status)
        show_status
        ;;
    auto)
        auto_control
        ;;
    *)
        echo "Usage: $0 {start|stop|status|auto}"
        echo ""
        echo "Commands:"
        echo "  start   - Start queue worker (enable compression)"
        echo "  stop    - Stop queue worker (disable compression)"
        echo "  status  - Show current queue worker status"
        echo "  auto    - Auto start/stop based on time (for cron)"
        echo ""
        echo "Examples:"
        echo "  $0 stop              # Pause compression manually"
        echo "  $0 start             # Resume compression manually"
        echo "  $0 status            # Check status"
        echo ""
        echo "Cron setup (auto control):"
        echo "  # Run every hour to auto-control queue"
        echo "  0 * * * * cd /var/www/analisis_videos && ./queue-control.sh auto >> storage/logs/queue-control.log 2>&1"
        exit 1
        ;;
esac
