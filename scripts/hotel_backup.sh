#!/bin/bash
# Hotel Reservation Centre - Automated Backup Script
set -o pipefail

SOURCE_DIR="/srv/hotel"
BACKUP_DIR="/backup"
LOG_FILE="/var/log/hotel_backup.log"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/hotel_backup_${TIMESTAMP}.tar.gz"
RETENTION_DAYS=7

log() {
    local LEVEL="$1"
    shift
    local MSG="$*"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [${LEVEL}] ${MSG}" | tee -a "${LOG_FILE}"
}

log "INFO" "=========================================================="
log "INFO" "Starting Hotel Server backup process..."

if [ ! -d "${SOURCE_DIR}" ] || [ ! -d "${BACKUP_DIR}" ]; then
    log "ERROR" "Source or Destination directory missing! Aborting."
    exit 1
fi

log "INFO" "Archiving ${SOURCE_DIR} to ${BACKUP_FILE}..."
if tar -czf "${BACKUP_FILE}" -C / srv/hotel 2>> "${LOG_FILE}"; then
    ARCHIVE_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    log "INFO" "Archive created successfully (Size: ${ARCHIVE_SIZE})."
else
    log "ERROR" "Failed to create backup archive!"
    exit 1
fi

log "INFO" "Performing archive integrity verification (tar -tzf)..."
if tar -tzf "${BACKUP_FILE}" > /dev/null 2>&1; then
    log "INFO" "Integrity test PASSED: Archive is valid and readable."
else
    log "ERROR" "Integrity test FAILED: Archive appears corrupted!"
    rm -f "${BACKUP_FILE}"
    exit 1
fi

log "INFO" "Applying ${RETENTION_DAYS}-day retention policy..."
find "${BACKUP_DIR}" -name "hotel_backup_*.tar.gz" -type f -mtime +${RETENTION_DAYS} -print -delete 2>/dev/null | while read -r f; do
    log "INFO" "  Purged expired archive: ${f}"
done

log "INFO" "Backup completed successfully!"
log "INFO" "=========================================================="
exit 0