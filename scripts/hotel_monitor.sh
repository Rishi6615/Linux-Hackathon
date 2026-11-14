#!/bin/bash
# Hotel Reservation Centre - System & Service Monitoring Script
LOG_FILE="/var/log/hotel_monitor.log"
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

log() {
    echo "[$TIMESTAMP] $*" | tee -a "$LOG_FILE"
}

log "=========================================================="
log "HOTEL SERVER HEALTH MONITOR REPORT"
log "=========================================================="

# 1. System Uptime
log "[SYSTEM] Uptime: $(uptime -p)"

# 2. CPU Load Average
CPU_LOAD=$(uptime | awk -F'load average:' '{print $2}' | xargs)
log "[CPU] Load Average (1m, 5m, 15m): ${CPU_LOAD}"

# 3. Memory Usage
MEM_TOTAL=$(free -m | awk '/Mem:/ {print $2}')
MEM_USED=$(free -m | awk '/Mem:/ {print $3}')
MEM_PERCENT=$(( MEM_USED * 100 / MEM_TOTAL ))
log "[MEMORY] Total: ${MEM_TOTAL}MB | Used: ${MEM_USED}MB (${MEM_PERCENT}%)"

# 4. Logged-in Users
USER_COUNT=$(who | wc -l)
log "[USERS] Currently Logged-in Users: ${USER_COUNT}"
who | while read -r line; do log "  Session: $line"; done

# 5. Disk Usage and 80% Threshold Check
log "[DISK] Filesystem Usage Summary:"
df -h -x tmpfs -x devtmpfs -x squashfs --output=source,size,used,avail,pcent,target | tail -n +2 | while read -r fs size used avail pcent mountpoint; do
    USAGE_NUM=$(echo "$pcent" | tr -d '%')
    log "  Mount: ${mountpoint} | Device: ${fs} | Used: ${used}/${size} (${pcent})"
    if [ "$USAGE_NUM" -ge 80 ]; then
        log "[WARNING] High disk usage on ${mountpoint} (${pcent} >= 80% threshold)!"
    fi
done

# 6. Service Health Monitoring & Automatic Self-Healing
SERVICES=("ssh" "apache2" "cron")
log "[SERVICES] Checking Core Daemons (ssh, apache2, cron):"

for SVC in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$SVC"; then
        log "  [OK] Service '${SVC}' is running and healthy."
    else
        log "  [ALERT] Service '${SVC}' is DOWN! Triggering automatic self-healing..."
        systemctl restart "$SVC"
        sleep 2
        if systemctl is-active --quiet "$SVC"; then
            log "  [RECOVERED] Service '${SVC}' successfully restarted and active."
        else
            log "  [CRITICAL] Service '${SVC}' failed to restart!"
        fi
    fi
done

log "=========================================================="
log "Report completed at ${TIMESTAMP}"
log ""
exit 0