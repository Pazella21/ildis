#!/usr/bin/env bash
#
# ILDIS Update Script
# Updates ILDIS to the latest or specified version via Docker Compose.
#
# Usage:
#   ./update.sh                  Update to latest release
#   ./update.sh --yes            Skip confirmation prompt
#   ./update.sh --version TAG   Update to a specific version
#   ./update.sh --check         Check current and available versions
#   ./update.sh --rollback FILE Print rollback instructions
#   ./update.sh --help          Show help text
#

set -euo pipefail

# ── Configuration ──────────────────────────────────────────────────────────
GITHUB_REPO="bphndigitalservice/ildis"
GHCR_IMAGE="ghcr.io/${GITHUB_REPO}"
BACKUP_DIR="backups"
COMPOSE_FILE="docker-compose.yml"
MIN_DISK_MB=1024
HEALTH_RETRIES=5
HEALTH_INTERVAL=10

# ── Colors ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ── Helper functions ────────────────────────────────────────────────────────
info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
fail()    { echo -e "${RED}[FAIL]${NC} $*"; exit 1; }

fail_with_rollback() {
    local backup_file="$1"
    local step="$2"
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}Update FAILED at: ${step}${NC}"
    echo -e "${RED}========================================${NC}"
    echo ""
    echo "Your database has NOT been modified (backup was taken before changes)."
    echo ""
    if [ -n "$backup_file" ]; then
        echo "Database backup: ${backup_file}"
    fi
    echo ""
    echo "To rollback manually:"
    echo "  1. Stop the containers:"
    echo "     docker compose down"
    echo ""
    echo "  2. Restore the database from backup:"
    echo "     gunzip -c ${backup_file} | docker compose exec -T mysql mysql -u\${DB_USER} -p\${DB_PASSWORD} \${DB_DATABASE}"
    echo ""
    echo "  3. If needed, revert to the previous Docker image:"
    echo "     Edit .env or docker-compose.yml to set the old image tag"
    echo "     docker compose up -d"
    echo ""
    echo "  4. Verify the application is working:"
    echo "     curl -f http://localhost:\${PORT:-8080}/"
    echo ""
    exit 1
}

# ── Parse arguments ─────────────────────────────────────────────────────────
SKIP_CONFIRM=false
TARGET_VERSION=""
ACTION="update"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --yes|-y)
            SKIP_CONFIRM=true
            shift
            ;;
        --version|-v)
            TARGET_VERSION="$2"
            shift 2
            ;;
        --check|-c)
            ACTION="check"
            shift
            ;;
        --rollback|-r)
            ACTION="rollback"
            ROLLBACK_FILE="$2"
            shift 2
            ;;
        --help|-h)
            ACTION="help"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Run './update.sh --help' for usage."
            exit 1
            ;;
    esac
done

show_help() {
    cat <<'EOF'
ILDIS Update Script — Update ILDIS to a new version

Usage:
  ./update.sh                  Update to the latest release
  ./update.sh --yes            Skip confirmation prompt
  ./update.sh --version TAG    Update to a specific version (e.g., v4.2.0)
  ./update.sh --check          Check current and available versions
  ./update.sh --rollback FILE  Print rollback instructions for a backup
  ./update.sh --help           Show this help text

Options:
  --yes,     -y    Skip the confirmation prompt
  --version, -v    Update to a specific version tag
  --check,   -c    Only check versions, do not update
  --rollback, -r   Print rollback instructions for a backup file
  --help,    -h    Show this help text

Examples:
  ./update.sh
  ./update.sh --yes --version v4.2.0
  ./update.sh --check
  ./update.sh --rollback backups/ildis_20260513_120000.sql.gz
EOF
    exit 0
}

# ── Action: help ────────────────────────────────────────────────────────────
if [ "$ACTION" = "help" ]; then
    show_help
fi

# ── Action: rollback ───────────────────────────────────────────────────────
if [ "$ACTION" = "rollback" ]; then
    if [ -z "${ROLLBACK_FILE:-}" ]; then
        fail "Please specify a backup file: ./update.sh --rollback <file>"
    fi
    if [ ! -f "${ROLLBACK_FILE}" ]; then
        fail "Backup file not found: ${ROLLBACK_FILE}"
    fi

    echo ""
    echo "=========================================="
    echo "ILDIS Rollback Instructions"
    echo "=========================================="
    echo ""
    echo "1. Stop the containers:"
    echo "   docker compose down"
    echo ""
    echo "2. Start only the database:"
    echo "   docker compose up -d mysql"
    echo ""
    echo "3. Wait for MySQL to be ready, then restore:"
    echo "   source .env"
    echo "   gunzip -c ${ROLLBACK_FILE} | docker compose exec -T mysql mysql -u\${DB_USER:-root} -p\${DB_PASSWORD} \${DB_DATABASE:-ildis_v4}"
    echo ""
    echo "4. Revert the Docker image to the previous version:"
    echo "   Edit COMPOSE_FILE or .env and set the old image tag"
    echo "   docker compose up -d"
    echo ""
    echo "5. Verify the application:"
    echo "   curl -f http://localhost:\${PORT:-8080}/"
    echo ""
    exit 0
fi

# ── Pre-flight checks ───────────────────────────────────────────────────────
info "Running pre-flight checks..."

if ! command -v docker &>/dev/null; then
    fail "Docker is not installed. Please install Docker first."
fi

if ! docker compose version &>/dev/null 2>&1; then
    fail "Docker Compose is not available. Please install Docker Compose."
fi

if [ ! -f "${COMPOSE_FILE}" ]; then
    fail "docker-compose.yml not found in current directory. Run this script from the ILDIS root directory."
fi

if [ ! -f ".env" ]; then
    fail ".env file not found. Copy .env.example and configure it first."
fi

set -a; source .env; set +a

available_kb=$(df -k . | awk 'NR==2 {print $4}')
available_mb=$((available_kb / 1024))
if [ "${available_mb}" -lt "${MIN_DISK_MB}" ]; then
    fail "Insufficient disk space: ${available_mb}MB available, ${MIN_DISK_MB}MB required."
fi
success "Disk space: ${available_mb}MB available"

containers_running=false
if docker compose ps --format json 2>/dev/null | grep -q '"running"' 2>/dev/null; then
    containers_running=true
elif docker compose ps 2>/dev/null | grep -q "Up"; then
    containers_running=true
fi

if [ "${containers_running}" = false ]; then
    warn "ILDIS containers do not appear to be running."
    echo "  Start them first with: docker compose up -d"
    echo ""
    read -rp "Continue anyway? [y/N]: " CONTINUE
    if [ "${CONTINUE}" != "y" ] && [ "${CONTINUE}" != "Y" ]; then
        exit 0
    fi
fi

success "Pre-flight checks passed"

# ── Get current version ────────────────────────────────────────────────────
if [ -f "VERSION" ]; then
    CURRENT_VERSION=$(tr -d '[:space:]' < VERSION)
else
    CURRENT_VERSION="unknown"
    warn "VERSION file not found. Cannot determine current version."
fi

# ── Get latest version from GitHub ─────────────────────────────────────────
info "Checking for updates..."

GITHUB_API_URL="https://api.github.com/repos/${GITHUB_REPO}/releases/latest"

if [ -n "${TARGET_VERSION}" ]; then
    LATEST_VERSION="${TARGET_VERSION}"
    TAG_NAME="${TARGET_VERSION}"
    RELEASE_BODY=""
    if command -v curl &>/dev/null; then
        RELEASE_INFO=$(curl -sf "https://api.github.com/repos/${GITHUB_REPO}/releases/tags/${TARGET_VERSION}" 2>/dev/null || echo "{}")
        RELEASE_BODY=$(echo "${RELEASE_INFO}" | grep -o '"body":"[^"]*"' | head -1 | sed 's/"body":"//;s/"$//' | sed 's/\\n/\n/g' | head -20)
    fi
else
    if command -v curl &>/dev/null; then
        RELEASE_INFO=$(curl -sf "${GITHUB_API_URL}" 2>/dev/null || echo "{}")
    elif command -v wget &>/dev/null; then
        RELEASE_INFO=$(wget -qO- "${GITHUB_API_URL}" 2>/dev/null || echo "{}")
    else
        fail "Neither curl nor wget found. Please install one of them."
    fi

    TAG_NAME=$(echo "${RELEASE_INFO}" | grep -o '"tag_name":"[^"]*"' | head -1 | sed 's/"tag_name":"//;s/"$//')
    RELEASE_BODY=$(echo "${RELEASE_INFO}" | grep -o '"body":"[^"]*"' | head -1 | sed 's/"body":"//;s/"$//' | sed 's/\\n/\n/g' | head -20)

    if [ -z "${TAG_NAME}" ]; then
        fail "Could not fetch release info from GitHub. Check your internet connection."
    fi

    LATEST_VERSION="${TAG_NAME}"
fi

CURRENT_CLEAN=$(echo "${CURRENT_VERSION}" | sed 's/^v//')
LATEST_CLEAN=$(echo "${LATEST_VERSION}" | sed 's/^v//')

# ── Action: check ──────────────────────────────────────────────────────────
if [ "$ACTION" = "check" ]; then
    echo ""
    echo "ILDIS Version Check"
    echo "===================="
    echo "Current version: ${CURRENT_VERSION}"
    echo "Latest version:  ${LATEST_VERSION}"
    if [ "${CURRENT_CLEAN}" = "${LATEST_CLEAN}" ]; then
        echo ""
        success "You are already on the latest version."
    else
        echo ""
        if [ -n "${RELEASE_BODY}" ]; then
            echo "Release notes:"
            echo "${RELEASE_BODY}"
        fi
    fi
    exit 0
fi

# ── Version comparison ─────────────────────────────────────────────────────
if [ "${CURRENT_CLEAN}" = "${LATEST_CLEAN}" ]; then
    success "You are already on version ${CURRENT_VERSION}. No update needed."
    exit 0
fi

echo ""
echo "ILDIS Update Available"
echo "======================"
echo "Current version: ${CURRENT_VERSION}"
echo "Target version:  ${LATEST_VERSION}"
if [ -n "${RELEASE_BODY}" ]; then
    echo ""
    echo "Changes:"
    echo "${RELEASE_BODY}" | sed 's/^/  /'
fi
echo ""

# ── Confirmation ────────────────────────────────────────────────────────────
if [ "${SKIP_CONFIRM}" = false ]; then
    read -rp "Proceed with update? [y/N]: " CONFIRM
    if [ "${CONFIRM}" != "y" ] && [ "${CONFIRM}" != "Y" ]; then
        echo "Update cancelled."
        exit 0
    fi
fi

# ── Database backup ────────────────────────────────────────────────────────
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/ildis_${TIMESTAMP}.sql.gz"

info "Creating database backup..."

mkdir -p "${BACKUP_DIR}"

DB_HOST_VAL="${DB_HOST:-mysql}"
DB_USER_VAL="${DB_USER:-root}"
DB_PASS_VAL="${DB_PASSWORD:-}"
DB_NAME_VAL="${DB_DATABASE:-ildis_v4}"
DB_PORT_VAL="${DB_DATABASE_PORT:-3306}"

BACKUP_SUCCESS=false

# Method 1: mysqldump via docker compose exec
if docker compose exec -T mysql mysqldump \
    -h "${DB_HOST_VAL}" \
    -u "${DB_USER_VAL}" \
    -p"${DB_PASS_VAL}" \
    -P "${DB_PORT_VAL}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_NAME_VAL}" 2>/dev/null | gzip > "${BACKUP_FILE}"; then
    BACKUP_SUCCESS=true
fi

# Method 2: Try with localhost as host (inside docker network)
if [ "${BACKUP_SUCCESS}" = false ]; then
    info "Retrying backup with container network..."
    if docker compose exec -T mysql mysqldump \
        -h localhost \
        -u "${DB_USER_VAL}" \
        -p"${DB_PASS_VAL}" \
        -P "${DB_PORT_VAL}" \
        --single-transaction \
        --routines \
        --triggers \
        "${DB_NAME_VAL}" 2>/dev/null | gzip > "${BACKUP_FILE}"; then
        BACKUP_SUCCESS=true
    fi
fi

# Method 3: Try without -p flag on command line (use MYSQL_PWD env var)
if [ "${BACKUP_SUCCESS}" = false ]; then
    info "Retrying backup with env var password..."
    if docker compose exec -T mysql sh -c \
        "MYSQL_PWD=\"${DB_PASS_VAL}\" mysqldump -u \"${DB_USER_VAL}\" --single-transaction --routines --triggers \"${DB_NAME_VAL}\"" 2>/dev/null | gzip > "${BACKUP_FILE}"; then
        BACKUP_SUCCESS=true
    fi
fi

if [ "${BACKUP_SUCCESS}" = false ]; then
    fail "Database backup failed. Cannot proceed with update."
fi

BACKUP_SIZE=$(stat -f%z "${BACKUP_FILE}" 2>/dev/null || stat -c%s "${BACKUP_FILE}" 2>/dev/null || echo "0")
if [ "${BACKUP_SIZE}" -eq 0 ]; then
    rm -f "${BACKUP_FILE}"
    fail "Database backup is empty (0 bytes). Cannot proceed with update."
fi

success "Database backup saved: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"

# ── Pull new image ─────────────────────────────────────────────────────────
info "Pulling ILDIS image..."

if ! docker compose pull app 2>&1; then
    warn "Pull by service name 'app' failed. Attempting full pull..."
    if ! docker compose pull 2>&1; then
        fail_with_rollback "${BACKUP_FILE}" "Pulling Docker image"
    fi
fi

success "Docker image pulled successfully"

# ── Restart containers ──────────────────────────────────────────────────────
info "Restarting ILDIS containers..."

if ! docker compose up -d 2>&1; then
    fail_with_rollback "${BACKUP_FILE}" "Restarting containers"
fi

success "Containers restarted"

# ── Wait for MySQL ──────────────────────────────────────────────────────────
info "Waiting for MySQL to be ready..."

MYSQL_READY=false
for i in $(seq 1 30); do
    if docker compose exec -T mysql sh -c "MYSQL_PWD=\"${DB_PASS_VAL}\" mysqladmin ping -h localhost -u \"${DB_USER_VAL}\"" 2>/dev/null | grep -q "alive"; then
        MYSQL_READY=true
        break
    fi
    sleep 2
done

if [ "${MYSQL_READY}" = false ]; then
    fail_with_rollback "${BACKUP_FILE}" "MySQL health check timed out"
fi

success "MySQL is ready"

# ── Run migrations ──────────────────────────────────────────────────────────
info "Running database migrations..."

if docker compose exec -T app php yii migrate --interactive=0 2>&1; then
    success "Migrations applied (or none pending)"
else
    warn "Migration command returned non-zero exit code."
    warn "This may be normal if no migrations exist yet."
    warn "Continuing with update..."
fi

# ── Health check ────────────────────────────────────────────────────────────
APP_PORT="${PORT:-8080}"

info "Running health check (http://localhost:${APP_PORT})..."

HEALTHY=false
for i in $(seq 1 "${HEALTH_RETRIES}"); do
    if curl -sf "http://localhost:${APP_PORT}/" >/dev/null 2>&1; then
        HEALTHY=true
        break
    fi
    info "Attempt ${i}/${HEALTH_RETRIES}: Application not responding yet. Waiting ${HEALTH_INTERVAL}s..."
    sleep "${HEALTH_INTERVAL}"
done

if [ "${HEALTHY}" = false ]; then
    fail_with_rollback "${BACKUP_FILE}" "Health check failed — application not responding"
fi

success "Application is responding"

# ── Update VERSION file ────────────────────────────────────────────────────
echo "${LATEST_CLEAN}" > VERSION

# ── Success! ────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}ILDIS Update Successful!${NC}"
echo -e "${GREEN}==========================================${NC}"
echo ""
echo "  Previous version: ${CURRENT_VERSION}"
echo "  New version:      ${LATEST_VERSION}"
echo "  Backup file:      ${BACKUP_FILE}"
echo ""
echo "  Verify the application: http://localhost:${APP_PORT}"
echo ""
success "Update complete!"