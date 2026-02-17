#!/bin/bash

################################################################################
# OPF Capital Dashboard - Database Recovery Script
#
# This script restores the OPF Capital Dashboard database from a backup.
#
# Features:
# - Lists available backups
# - Validates backup file integrity
# - Creates safety backup before restore
# - Restores database from backup file
# - Logs all operations
#
# Usage:
#   ./restore-database.sh [backup-file.sql.gz]
#
# If no backup file specified, shows list of available backups.
#
# Requirements:
# - MySQL client installed
# - .env file with database credentials
# - Read permissions to backup directory
#
# DANGER: This will overwrite the current database!
#
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
LOG_FILE="$PROJECT_ROOT/storage/logs/restore-$(date +"%Y-%m-%d").log"

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

    export $(grep -v '^#' "$ENV_FILE" | grep -v '^$' | xargs)
}

# Function: List available backups
list_backups() {
    BACKUP_DIR="$PROJECT_ROOT/${BACKUP_PATH:-storage/backups}"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        error "Backup directory not found: $BACKUP_DIR"
        exit 1
    fi

    echo ""
    echo "Available backups in $BACKUP_DIR:"
    echo "=================================================="
    
    ls -lh "$BACKUP_DIR"/*.sql.gz 2>/dev/null | awk '{print $9, "(" $5 ")", $6, $7, $8}' || echo "No backups found"
    
    echo "=================================================="
    echo ""
}

# Function: Validate backup file
validate_backup_file() {
    BACKUP_FILE="$1"
    
    if [ ! -f "$BACKUP_FILE" ]; then
        error "Backup file not found: $BACKUP_FILE"
        return 1
    fi

    log "Validating backup file integrity..."
    
    if gzip -t "$BACKUP_FILE" 2>/dev/null; then
        log "Backup file integrity OK"
        return 0
    else
        error "Backup file is corrupted or invalid"
        return 1
    fi
}

# Function: Create safety backup
create_safety_backup() {
    log "Creating safety backup of current database..."
    
    SAFETY_BACKUP="$PROJECT_ROOT/storage/backups/pre-restore-safety_${TIMESTAMP}.sql.gz"
    
    if mysqldump \
        --host="${DB_HOST:-127.0.0.1}" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        "$DB_DATABASE" | gzip > "$SAFETY_BACKUP"; then
        
        log "Safety backup created: $SAFETY_BACKUP"
        return 0
    else
        error "Failed to create safety backup"
        return 1
    fi
}

# Function: Restore database
restore_database() {
    BACKUP_FILE="$1"
    
    log "========================================="
    log "WARNING: This will overwrite the database!"
    log "Database: $DB_DATABASE"
    log "Backup file: $BACKUP_FILE"
    log "========================================="
    
    # Confirmation prompt (skip if -y flag provided)
    if [ "${AUTO_CONFIRM:-0}" != "1" ]; then
        read -p "Are you sure you want to continue? (yes/no): " CONFIRM
        if [ "$CONFIRM" != "yes" ]; then
            log "Restore cancelled by user"
            exit 0
        fi
    fi

    log "Starting database restore..."
    
    # Decompress and restore
    if gunzip -c "$BACKUP_FILE" | mysql \
        --host="${DB_HOST:-127.0.0.1}" \
        --port="${DB_PORT:-3306}" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        "$DB_DATABASE"; then
        
        log "Database restore completed successfully"
        return 0
    else
        error "Database restore failed"
        return 1
    fi
}

# Function: Verify restored database
verify_restore() {
    log "Verifying restored database..."
    
    # Check if essential tables exist
    TABLES=("users" "roles" "projects" "expenses" "opportunities")
    
    for TABLE in "${TABLES[@]}"; do
        if mysql \
            --host="${DB_HOST:-127.0.0.1}" \
            --port="${DB_PORT:-3306}" \
            --user="$DB_USERNAME" \
            --password="$DB_PASSWORD" \
            "$DB_DATABASE" \
            -e "DESCRIBE $TABLE" > /dev/null 2>&1; then
            log "Table verified: $TABLE"
        else
            error "Table missing or invalid: $TABLE"
            return 1
        fi
    done
    
    log "Database verification complete"
    return 0
}

# Main execution
main() {
    BACKUP_FILE="${1:-}"
    
    # Load configuration
    load_env
    
    # If no backup file specified, list available backups
    if [ -z "$BACKUP_FILE" ]; then
        list_backups
        echo "Usage: $0 <backup-file.sql.gz>"
        exit 0
    fi

    log "========================================="
    log "Starting restore process"
    log "========================================="
    
    # Validate backup file
    if ! validate_backup_file "$BACKUP_FILE"; then
        exit 1
    fi

    # Create safety backup
    if ! create_safety_backup; then
        error "Cannot proceed without safety backup"
        exit 1
    fi

    # Restore database
    if restore_database "$BACKUP_FILE"; then
        # Verify restoration
        if verify_restore; then
            log "========================================="
            log "Restore process completed successfully"
            log "========================================="
            
            # Clear application cache
            log "Clearing application cache..."
            cd "$PROJECT_ROOT"
            php artisan cache:clear > /dev/null 2>&1 || true
            php artisan config:clear > /dev/null 2>&1 || true
            
            exit 0
        else
            error "Restore verification failed"
            exit 1
        fi
    else
        error "Restore process failed"
        log "You can restore from safety backup: pre-restore-safety_${TIMESTAMP}.sql.gz"
        exit 1
    fi
}

# Run main function
main "$@"
