#!/bin/bash

################################################################################
# OPF Capital Dashboard - Database Backup Script
#
# This script creates automated backups of the OPF Capital Dashboard database.
#
# Features:
# - Creates timestamped SQL dumps
# - Compresses backups with gzip
# - Implements retention policy (configurable days)
# - Logs all operations
# - Supports offsite backup transfer (optional)
#
# Usage:
#   ./backup-database.sh
#
# Schedule with cron:
#   0 2 * * * /var/www/opf-cd/scripts/backup-database.sh
#
# Requirements:
# - MySQL client installed
# - .env file with database credentials
# - Write permissions to backup directory
#
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
LOG_FILE="$PROJECT_ROOT/storage/logs/backup-$(date +"%Y-%m-%d").log"

# Function: Log messages
log() {
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1" | tee -a "$LOG_FILE"
}

# Function: Log errors
error() {
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] ERROR: $1" | tee -a "$LOG_FILE" >&2
}

# Function: Load environment variables
load_env() {
    if [ ! -f "$ENV_FILE" ]; then
        error ".env file not found at $ENV_FILE"
        exit 1
    fi

    # Parse .env file (simple implementation, adjust if needed)
    export $(grep -v '^#' "$ENV_FILE" | grep -v '^$' | xargs)
}

# Function: Validate configuration
validate_config() {
    if [ -z "${DB_DATABASE:-}" ]; then
        error "DB_DATABASE not set in .env"
        exit 1
    fi

    if [ -z "${DB_USERNAME:-}" ]; then
        error "DB_USERNAME not set in .env"
        exit 1
    fi

    if [ -z "${BACKUP_PATH:-storage/backups}" ]; then
        log "BACKUP_PATH not set, using default: storage/backups"
        BACKUP_PATH="storage/backups"
    fi

    if [ -z "${BACKUP_RETENTION_DAYS:-30}" ]; then
        log "BACKUP_RETENTION_DAYS not set, using default: 30"
        BACKUP_RETENTION_DAYS=30
    fi
}

# Function: Create backup directory
create_backup_dir() {
    BACKUP_DIR="$PROJECT_ROOT/$BACKUP_PATH"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        log "Creating backup directory: $BACKUP_DIR"
        mkdir -p "$BACKUP_DIR"
        chmod 750 "$BACKUP_DIR"
    fi
}

# Function: Perform database backup
backup_database() {
    BACKUP_FILE="$BACKUP_DIR/${DB_DATABASE}_${TIMESTAMP}.sql"
    BACKUP_FILE_GZ="${BACKUP_FILE}.gz"

    log "Starting database backup: $DB_DATABASE"
    log "Backup file: $BACKUP_FILE_GZ"

    # Perform mysqldump
    if mysqldump \
        --host="${DB_HOST:-127.0.0.1}" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        "$DB_DATABASE" > "$BACKUP_FILE"; then
        
        log "Database dump successful"
        
        # Compress backup
        log "Compressing backup..."
        gzip "$BACKUP_FILE"
        
        # Set permissions
        chmod 640 "$BACKUP_FILE_GZ"
        
        # Get file size
        SIZE=$(du -h "$BACKUP_FILE_GZ" | cut -f1)
        log "Backup complete: $BACKUP_FILE_GZ ($SIZE)"
        
        return 0
    else
        error "Database backup failed"
        return 1
    fi
}

# Function: Clean old backups
clean_old_backups() {
    log "Cleaning backups older than ${BACKUP_RETENTION_DAYS} days"
    
    DELETED_COUNT=$(find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f -mtime +${BACKUP_RETENTION_DAYS} -delete -print | wc -l)
    
    if [ "$DELETED_COUNT" -gt 0 ]; then
        log "Deleted $DELETED_COUNT old backup(s)"
    else
        log "No old backups to delete"
    fi
}

# Function: Optional offsite backup
offsite_backup() {
    # This is a placeholder for offsite backup integration
    # Implement based on your offsite storage solution (AWS S3, Azure Blob, etc.)
    
    if [ -n "${AWS_S3_BUCKET:-}" ]; then
        log "Transferring to AWS S3: ${AWS_S3_BUCKET}"
        # aws s3 cp "$BACKUP_FILE_GZ" "s3://${AWS_S3_BUCKET}/backups/"
        log "Offsite backup transfer complete"
    fi
}

# Function: Verify backup integrity
verify_backup() {
    BACKUP_FILE_GZ="$1"
    
    log "Verifying backup integrity..."
    
    if gzip -t "$BACKUP_FILE_GZ" 2>/dev/null; then
        log "Backup integrity verified"
        return 0
    else
        error "Backup file is corrupted"
        return 1
    fi
}

# Main execution
main() {
    log "========================================="
    log "Starting backup process"
    log "========================================="
    
    # Load configuration
    load_env
    validate_config
    
    # Create backup directory
    create_backup_dir
    
    # Perform backup
    if backup_database; then
        BACKUP_FILE_GZ="$BACKUP_DIR/${DB_DATABASE}_${TIMESTAMP}.sql.gz"
        
        # Verify backup
        if verify_backup "$BACKUP_FILE_GZ"; then
            # Clean old backups
            clean_old_backups
            
            # Optional: Transfer to offsite storage
            # offsite_backup
            
            log "========================================="
            log "Backup process completed successfully"
            log "========================================="
            exit 0
        else
            error "Backup verification failed"
            exit 1
        fi
    else
        error "Backup process failed"
        exit 1
    fi
}

# Run main function
main
