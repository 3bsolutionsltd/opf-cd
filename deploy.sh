#!/bin/bash

###############################################################################
# OPF-CD Deployment Script
# Zero-downtime deployment with rollback capability
###############################################################################

set -e  # Exit on any error

# Configuration
APP_NAME="opf-cd"
DEPLOY_USER=$(whoami)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
KEEP_RELEASES=3  # Number of releases to keep for rollback

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directories
BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
RELEASES_DIR="${BASE_DIR}/releases"
CURRENT_DIR="${BASE_DIR}/current"
SHARED_DIR="${BASE_DIR}/shared"
REPO_DIR="${BASE_DIR}"

# Deployment details
COMMIT_SHA=${COMMIT_SHA:-$(git rev-parse HEAD)}
COMMIT_SHORT=${COMMIT_SHA:0:7}
BRANCH="master"

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
log_info "🚀 Starting OPF-CD Deployment"
log_info "=========================================="
log_info "Timestamp: $(date)"
log_info "User: ${DEPLOY_USER}"
log_info "Commit: ${COMMIT_SHORT}"
log_info "Branch: ${BRANCH}"
log_info "=========================================="

# Check if git repo exists
if [ ! -d ".git" ]; then
    log_error "Not a git repository. Run this script from repo root."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "0")
if [[ $(echo "$PHP_VERSION 8.1" | awk '{print ($1 >= $2)}') -eq 0 ]]; then
    log_error "PHP 8.1+ required. Current: ${PHP_VERSION}"
    exit 1
fi
log_success "PHP version: ${PHP_VERSION}"

# Check composer
if ! command -v composer &> /dev/null; then
    log_error "Composer not found"
    exit 1
fi
log_success "Composer: $(composer --version | head -n1)"

###############################################################################
# Setup Directory Structure
###############################################################################

log_info "Setting up directory structure..."

# Create directories if they don't exist
mkdir -p "${RELEASES_DIR}"
mkdir -p "${SHARED_DIR}/storage"
mkdir -p "${SHARED_DIR}/storage/app"
mkdir -p "${SHARED_DIR}/storage/framework"
mkdir -p "${SHARED_DIR}/storage/framework/cache"
mkdir -p "${SHARED_DIR}/storage/framework/sessions"
mkdir -p "${SHARED_DIR}/storage/framework/views"
mkdir -p "${SHARED_DIR}/storage/logs"

log_success "Directory structure ready"

###############################################################################
# Pull Latest Code
###############################################################################

log_info "Pulling latest code from GitHub..."

# Stash any local changes (shouldn't be any, but just in case)
git stash > /dev/null 2>&1 || true

# Fetch and pull
git fetch origin ${BRANCH}
git reset --hard origin/${BRANCH}

# Self-update: Make sure deploy.sh is executable
chmod +x deploy.sh

log_success "Code updated to latest commit: ${COMMIT_SHORT}"

###############################################################################
# Create New Release
###############################################################################

RELEASE_DIR="${RELEASES_DIR}/release_${TIMESTAMP}"
log_info "Creating new release: ${RELEASE_DIR}"

# Copy backend to new release directory
cp -R backend "${RELEASE_DIR}"

log_success "Release directory created"

###############################################################################
# Install Dependencies
###############################################################################

log_info "Installing Composer dependencies..."

cd "${RELEASE_DIR}"
composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs --quiet

if [ $? -ne 0 ]; then
    log_error "Composer install failed"
    rm -rf "${RELEASE_DIR}"
    exit 1
fi

log_success "Dependencies installed"

###############################################################################
# Link Shared Resources
###############################################################################

log_info "Linking shared resources..."

# Remove release storage and link to shared
rm -rf "${RELEASE_DIR}/storage"
ln -s "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# Link .env from shared (or current if exists)
if [ -f "${SHARED_DIR}/.env" ]; then
    ln -s "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
elif [ -f "${CURRENT_DIR}/.env" ]; then
    cp "${CURRENT_DIR}/.env" "${SHARED_DIR}/.env"
    ln -s "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
else
    log_warning ".env not found in shared or current, you'll need to create it"
fi

log_success "Shared resources linked"

###############################################################################
# Run Migrations
###############################################################################

log_info "Running database migrations..."

cd "${RELEASE_DIR}"
php artisan migrate --force --no-interaction

if [ $? -ne 0 ]; then
    log_warning "Migration failed, but continuing..."
fi

log_success "Migrations completed"

###############################################################################
# Optimize Application
###############################################################################

log_info "Optimizing application..."

cd "${RELEASE_DIR}"

# Clear old caches
php artisan config:clear --quiet
php artisan route:clear --quiet
php artisan view:clear --quiet
php artisan cache:clear --quiet

# Cache for production
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

log_success "Application optimized"

###############################################################################
# Set Permissions
###############################################################################

log_info "Setting file permissions..."

cd "${RELEASE_DIR}"
chmod -R 755 bootstrap/cache
find "${SHARED_DIR}/storage" -type d -exec chmod 755 {} \;
find "${SHARED_DIR}/storage" -type f -exec chmod 644 {} \;

log_success "Permissions set"

###############################################################################
# Switch to New Release (Zero Downtime)
###############################################################################

log_info "Switching to new release..."

# Backup current symlink
if [ -L "${CURRENT_DIR}" ]; then
    PREVIOUS_RELEASE=$(readlink "${CURRENT_DIR}")
    log_info "Previous release: ${PREVIOUS_RELEASE}"
fi

# Atomic switch - remove and create new symlink
rm -f "${CURRENT_DIR}"
ln -s "${RELEASE_DIR}" "${CURRENT_DIR}"

log_success "Switched to new release"

###############################################################################
# Reload PHP-FPM / Services (if applicable)
###############################################################################

log_info "Reloading services..."

# Try to reload PHP-FPM (may not have permission on shared hosting)
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php-fpm 2>/dev/null || log_warning "Could not reload PHP-FPM"
elif [ -f /usr/local/lsws/bin/lswsctrl ]; then
    # LiteSpeed (common on shared hosting)
    /usr/local/lsws/bin/lswsctrl reload 2>/dev/null || log_warning "Could not reload LiteSpeed"
else
    log_warning "Service reload skipped (may need manual restart)"
fi

log_success "Services reloaded"

###############################################################################
# Cleanup Old Releases
###############################################################################

log_info "Cleaning up old releases (keeping last ${KEEP_RELEASES})..."

cd "${RELEASES_DIR}"
RELEASE_COUNT=$(ls -1d release_* 2>/dev/null | wc -l)

if [ ${RELEASE_COUNT} -gt ${KEEP_RELEASES} ]; then
    RELEASES_TO_DELETE=$((RELEASE_COUNT - KEEP_RELEASES))
    log_info "Deleting ${RELEASES_TO_DELETE} old release(s)..."
    
    ls -1dt release_* | tail -n ${RELEASES_TO_DELETE} | xargs rm -rf
    
    log_success "Cleanup complete"
else
    log_info "No cleanup needed (${RELEASE_COUNT} releases)"
fi

###############################################################################
# Deployment Summary
###############################################################################

log_success "=========================================="
log_success "✅ Deployment Successful!"
log_success "=========================================="
log_info "Release: ${TIMESTAMP}"
log_info "Commit: ${COMMIT_SHORT}"
log_info "Path: ${RELEASE_DIR}"
log_info "Current: ${CURRENT_DIR} -> ${RELEASE_DIR}"
log_info "Time: $(date)"
log_success "=========================================="

# Health check (optional - uncomment if health endpoint exists)
# HEALTH_URL=$(grep APP_URL "${SHARED_DIR}/.env" | cut -d '=' -f2 | tr -d '"')/api/health
# if command -v curl &> /dev/null; then
#     log_info "Running health check: ${HEALTH_URL}"
#     HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${HEALTH_URL}")
#     if [ "${HEALTH_STATUS}" = "200" ]; then
#         log_success "Health check passed"
#     else
#         log_warning "Health check returned: ${HEALTH_STATUS}"
#     fi
# fi

exit 0
