#!/usr/bin/env bash
#
# ILDIS One-Click Install
# Deploy ILDIS with a single command:
#   curl -fsSL https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh | bash
#
# Usage:
#   ./install.sh                  Interactive install
#   ./install.sh --non-interactive  Use env vars, no prompts
#   ./install.sh --update          Update existing installation
#   ./install.sh --help            Show help text

set -euo pipefail

# ── Configuration ──────────────────────────────────────────────────────────
GITHUB_REPO="bphndigitalservice/ildis"
GHCR_IMAGE="ghcr.io/${GITHUB_REPO}"
COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env"
VERSION_FILE="VERSION"
BACKUP_DIR="backups"
DEFAULT_PORT=8080
DEFAULT_INSTALL_DIR="/opt/ildis"
MIN_DISK_MB=1024
HEALTH_RETRIES=12
HEALTH_INTERVAL=5
MYSQL_HEALTH_RETRIES=30
MYSQL_HEALTH_INTERVAL=2

# ── Colors ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ── Helper functions ────────────────────────────────────────────────────────
info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
fail()    { echo -e "${RED}[FAIL]${NC} $*"; exit 1; }

generate_random_key() {
    if command -v openssl &>/dev/null; then
        openssl rand -base64 32 | tr -d '=\n'
    elif [ -r /dev/urandom ]; then
        head -c 32 /dev/urandom | base64 | tr -d '=\n'
    else
        echo "RANDOM_KEY_$(date +%s)_$$"
    fi
}

confirm() {
    local prompt="$1"
    local default="${2:-n}"
    if [ "${NON_INTERACTIVE}" = true ]; then
        echo "$default"
        return
    fi
    local choices=""
    if [ "$default" = "y" ]; then
        choices="[Y/n]"
    else
        choices="[y/N]"
    fi
    read -rp "${prompt} ${choices} " REPLY
    REPLY="${REPLY:-$default}"
    [[ "$REPLY" =~ ^[Yy]$ ]]
}

prompt_value() {
    local prompt="$1"
    local default="$2"
    local is_secret="${3:-false}"
    if [ "${NON_INTERACTIVE}" = true ]; then
        echo "$default"
        return
    fi
    if [ "$is_secret" = true ]; then
        read -rsp "${prompt} [generated]: " REPLY
        echo ""
        REPLY="${REPLY:-$default}"
    else
        read -rp "${prompt} [${default}]: " REPLY
        REPLY="${REPLY:-$default}"
    fi
    echo "$REPLY"
}

# ── Parse arguments ─────────────────────────────────────────────────────────
INSTALL_DIR="${INSTALL_DIR:-}"
ACTION="install"
NON_INTERACTIVE=false
DB_TYPE_OVERRIDE=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --non-interactive|-n)
            NON_INTERACTIVE=true
            shift
            ;;
        --update|-u)
            ACTION="update"
            shift
            ;;
        --dir|-d)
            INSTALL_DIR="$2"
            shift 2
            ;;
        --db-type|-t)
            DB_TYPE_OVERRIDE="$2"
            shift 2
            ;;
        --help|-h)
            ACTION="help"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Run './install.sh --help' for usage."
            exit 1
            ;;
    esac
done

show_help() {
    cat <<'EOF'
ILDIS One-Click Install — Deploy ILDIS with Docker

Usage:
  ./install.sh                      Interactive install
  ./install.sh --non-interactive    Use env vars, no prompts
  ./install.sh --update             Update existing installation
  ./install.sh --dir /opt/ildis    Specify install directory
  ./install.sh --db-type mariadb   Set DB type (mariadb|mysql|external)
  ./install.sh --help               Show this help text

Environment variables (for --non-interactive):
  INSTALL_DIR        Install directory (default: /opt/ildis)
  PORT               App port (default: 8080)
  PUBLIC_DOMAIN      Public URL (default: http://localhost:8080)
  DB_TYPE            Database type: mariadb, mysql, external
  DB_HOST            Database host (for external)
  DB_USER            Database user
  DB_PASSWORD        Database password
  DB_DATABASE        Database name (default: ildis_v4)
  DB_DATABASE_PORT   Database port (default: 3306)
  RECAPTCHA_SITE_KEY    reCAPTCHA site key
  RECAPTCHA_SECRET_KEY  reCAPTCHA secret key

Examples:
  curl -fsSL https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh | bash
  ./install.sh --non-interactive --db-type external
  ./install.sh --update
EOF
    exit 0
}

if [ "$ACTION" = "help" ]; then
    show_help
fi

# ── Prerequisite checks ─────────────────────────────────────────────────────
check_prerequisites() {
    info "Checking prerequisites..."

    if ! command -v docker &>/dev/null; then
        echo ""
        echo -e "${RED}Docker is not installed.${NC}"
        echo ""
        echo "Install Docker: https://docs.docker.com/engine/install/"
        fail "Docker is required."
    fi

    if ! docker compose version &>/dev/null 2>&1; then
        echo ""
        echo -e "${RED}Docker Compose v2 is not available.${NC}"
        echo ""
        echo "Install Docker Compose: https://docs.docker.com/compose/install/"
        fail "Docker Compose v2 is required."
    fi

    local available_kb available_mb
    available_kb=$(df -k . | awk 'NR==2 {print $4}')
    available_mb=$((available_kb / 1024))
    if [ "${available_mb}" -lt "${MIN_DISK_MB}" ]; then
        fail "Insufficient disk space: ${available_mb}MB available, ${MIN_DISK_MB}MB required."
    fi
    success "Disk space: ${available_mb}MB available"

    local port="${PORT:-${DEFAULT_PORT}}"
    if command -v ss &>/dev/null; then
        if ss -tlnp 2>/dev/null | grep -q ":${port} "; then
            warn "Port ${port} appears to be in use."
            warn "You can change it with PORT= when running the script."
        fi
    elif command -v lsof &>/dev/null; then
        if lsof -i ":${port}" &>/dev/null 2>&1; then
            warn "Port ${port} appears to be in use."
        fi
    fi

    success "Prerequisites OK"
}

# ── Interactive wizard ───────────────────────────────────────────────────────
run_wizard() {
    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║        ILDIS Installation Wizard         ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
    echo ""

    INSTALL_DIR=$(prompt_value "Install directory" "${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}")
    echo ""

    PORT=$(prompt_value "App port" "${PORT:-${DEFAULT_PORT}}")
    echo ""

    PUBLIC_DOMAIN=$(prompt_value "Public domain URL" "${PUBLIC_DOMAIN:-http://localhost:${PORT}}")
    echo ""

    echo -e "${BOLD}Database configuration:${NC}"
    echo "  1) MariaDB 10.11 (recommended, default)"
    echo "  2) MySQL 8.0"
    echo "  3) External database"
    echo ""
    if [ -n "${DB_TYPE_OVERRIDE}" ]; then
        DB_TYPE="${DB_TYPE_OVERRIDE}"
    else
        local db_choice
        db_choice=$(prompt_value "Choose database type (1/2/3)" "1")
        case "$db_choice" in
            2) DB_TYPE="mysql" ;;
            3) DB_TYPE="external" ;;
            *) DB_TYPE="mariadb" ;;
        esac
    fi

    DB_PASSWORD="${DB_PASSWORD:-}"
    if [ "${DB_TYPE}" = "external" ]; then
        echo ""
        echo -e "${CYAN}External database selected. Provide connection details:${NC}"
        DB_HOST=$(prompt_value "  Database host" "${DB_HOST:-}")
        DB_DATABASE_PORT=$(prompt_value "  Database port" "${DB_DATABASE_PORT:-3306}")
        DB_USER=$(prompt_value "  Database user" "${DB_USER:-ildis}")
        DB_DATABASE=$(prompt_value "  Database name" "${DB_DATABASE:-ildis_v4}")
        DB_PASSWORD=$(prompt_value "  Database password" "" "true")
        if [ -z "${DB_PASSWORD}" ]; then
            fail "Database password is required for external database."
        fi
    else
        local db_label="MariaDB 10.11"
        [ "${DB_TYPE}" = "mysql" ] && db_label="MySQL 8.0"
        echo ""
        echo -e "${CYAN}${db_label} selected.${NC}"

        if [ -z "${DB_PASSWORD}" ]; then
            DB_PASSWORD=$(generate_random_key)
            echo -e "  Generated database password: ${YELLOW}${DB_PASSWORD}${NC}"
            echo "  Save this password in a secure location!"
        fi
        DB_USER=$(prompt_value "  Database user" "${DB_USER:-ildis}")
        DB_DATABASE=$(prompt_value "  Database name" "${DB_DATABASE:-ildis_v4}")
    fi
    echo ""

    echo -e "${BOLD}reCAPTCHA (optional — press Enter to skip):${NC}"
    RECAPTCHA_SITE_KEY=$(prompt_value "  reCAPTCHA site key" "${RECAPTCHA_SITE_KEY:-}")
    if [ -n "${RECAPTCHA_SITE_KEY}" ]; then
        RECAPTCHA_SECRET_KEY=$(prompt_value "  reCAPTCHA secret key" "${RECAPTCHA_SECRET_KEY:-}")
    else
        RECAPTCHA_SECRET_KEY=""
    fi
    echo ""

    COOKIE_VALIDATION_KEY_BE="${COOKIE_VALIDATION_KEY_BE:-$(generate_random_key)}"
    COOKIE_VALIDATION_KEY_FE="${COOKIE_VALIDATION_KEY_FE:-$(generate_random_key)}"

    YII_ENV="${YII_ENV:-prod}"
    YII_DEBUG="${YII_DEBUG:-false}"

    echo ""
    echo -e "${BOLD}════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Installation Summary:${NC}"
    echo -e "${BOLD}════════════════════════════════════════════${NC}"
    echo "  Directory:    ${INSTALL_DIR}"
    echo "  App port:     ${PORT}"
    echo "  Domain:       ${PUBLIC_DOMAIN}"
    echo "  Database:     ${DB_TYPE}"
    if [ "${DB_TYPE}" = "external" ]; then
        echo "  DB host:      ${DB_HOST}:${DB_DATABASE_PORT}"
    fi
    echo "  DB user:      ${DB_USER}"
    echo "  DB name:      ${DB_DATABASE}"
    echo "  reCAPTCHA:    $([ -n "${RECAPTCHA_SITE_KEY}" ] && echo "configured" || echo "skipped")"
    echo ""

    if [ "${NON_INTERACTIVE}" = false ]; then
        if ! confirm "Proceed with installation?" "y"; then
            echo "Installation cancelled."
            exit 0
        fi
    fi
}

# ── Generate .env file ──────────────────────────────────────────────────────
generate_env() {
    info "Generating ${ENV_FILE}..."

    cat > "${INSTALL_DIR}/${ENV_FILE}" <<EOF
# ILDIS Environment Configuration
# Generated by install.sh on $(date -Iseconds 2>/dev/null || date)
# Review and update as needed.

# ── Application ──
YII_ENV=${YII_ENV}
YII_DEBUG=${YII_DEBUG}
PUBLIC_DOMAIN=${PUBLIC_DOMAIN}

# ── Database ──
DB_TYPE=${DB_TYPE}
DB_HOST=${DB_HOST:-mysql}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_DATABASE=${DB_DATABASE}
DB_DATABASE_PORT=${DB_DATABASE_PORT:-3306}

# ── Cookie validation (auto-generated) ──
COOKIE_VALIDATION_KEY_BE=${COOKIE_VALIDATION_KEY_BE}
COOKIE_VALIDATION_KEY_FE=${COOKIE_VALIDATION_KEY_FE}

# ── reCAPTCHA (optional) ──
RECAPTCHA_SITE_KEY=${RECAPTCHA_SITE_KEY:-}
RECAPTCHA_SECRET_KEY=${RECAPTCHA_SECRET_KEY:-}

# ── Docker image tag ──
ILDIS_IMAGE_TAG=${ILDIS_IMAGE_TAG:-latest}
EOF

    chmod 600 "${INSTALL_DIR}/${ENV_FILE}"
    success "${ENV_FILE} generated"
}

# ── Generate docker-compose.yml ─────────────────────────────────────────────
generate_compose() {
    info "Generating ${COMPOSE_FILE}..."

    local DB_IMAGE=""
    local DB_CONTAINER_NAME=""
    local DB_HEALTHCHECK=""

    if [ "${DB_TYPE}" = "mariadb" ]; then
        DB_IMAGE="mariadb:10.11"
        DB_CONTAINER_NAME="ildis_mariadb"
        DB_HEALTHCHECK='test: [ "CMD-SHELL", "healthcheck.sh --connect --innodb_initialized" ]
      interval: 10s
      timeout: 5s
      retries: 5'
    elif [ "${DB_TYPE}" = "mysql" ]; then
        DB_IMAGE="mysql:8.0"
        DB_CONTAINER_NAME="ildis_mysql"
        DB_HEALTHCHECK='test: [ "CMD-SHELL", "mysqladmin ping -h localhost -u root -p$${MYSQL_ROOT_PASSWORD}" ]
      interval: 10s
      timeout: 5s
      retries: 5'
    fi

    if [ "${DB_TYPE}" = "external" ]; then
        cat > "${INSTALL_DIR}/${COMPOSE_FILE}" <<'COMPOSEEOF'
services:
  app:
    image: ghcr.io/bphndigitalservice/ildis:${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_app
    restart: unless-stopped
    ports:
      - "${PORT:-8080}:80"
    volumes:
      - runtime:/var/www/runtime
      - backend-assets:/var/www/backend/web/assets
      - frontend-assets:/var/www/frontend/web/assets
      - backend-uploads:/var/www/backend/web/uploads
      - frontend-uploads:/var/www/frontend/web/uploads
      - feed_data:/var/www/feed
    environment:
      - S6_KEEP_ENV=1
      - YII_ENV=${YII_ENV:-prod}
      - YII_DEBUG=${YII_DEBUG:-false}
      - DB_HOST=${DB_HOST}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_DATABASE=${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=${DB_DATABASE_PORT:-3306}
      - PUBLIC_DOMAIN=${PUBLIC_DOMAIN:-http://localhost:8080}
      - COOKIE_VALIDATION_KEY_BE=${COOKIE_VALIDATION_KEY_BE}
      - COOKIE_VALIDATION_KEY_FE=${COOKIE_VALIDATION_KEY_FE}
      - RECAPTCHA_SITE_KEY=${RECAPTCHA_SITE_KEY:-}
      - RECAPTCHA_SECRET_KEY=${RECAPTCHA_SECRET_KEY:-}
      - PHP_DISPLAY_ERRORS=Off
      - PHP_ERROR_REPORTING=E_ALL & ~E_DEPRECATED
    healthcheck:
      test: [ "CMD", "curl", "-f", "http://localhost/" ]
      interval: 30s
      timeout: 10s
      retries: 3

  cron:
    image: ghcr.io/bphndigitalservice/ildis-cron:${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_cron
    restart: unless-stopped
    volumes:
      - feed_data:/var/www/feed
    environment:
      - YII_ENV=${YII_ENV:-prod}
      - YII_DEBUG=${YII_DEBUG:-false}
      - DB_HOST=${DB_HOST}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_DATABASE=${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=${DB_DATABASE_PORT:-3306}

volumes:
  runtime:
  backend-assets:
  frontend-assets:
  backend-uploads:
  frontend-uploads:
  feed_data:
COMPOSEEOF
    else
        cat > "${INSTALL_DIR}/${COMPOSE_FILE}" <<EOF
services:
  db:
    image: ${DB_IMAGE}
    container_name: ${DB_CONTAINER_NAME}
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: \${DB_PASSWORD}
      MYSQL_DATABASE: \${DB_DATABASE:-ildis_v4}
      MYSQL_USER: \${DB_USER:-ildis}
      MYSQL_PASSWORD: \${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
${DB_HEALTHCHECK}

  app:
    image: ghcr.io/bphndigitalservice/ildis:\${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_app
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "\${PORT:-8080}:80"
    volumes:
      - runtime:/var/www/runtime
      - backend-assets:/var/www/backend/web/assets
      - frontend-assets:/var/www/frontend/web/assets
      - backend-uploads:/var/www/backend/web/uploads
      - frontend-uploads:/var/www/frontend/web/uploads
      - feed_data:/var/www/feed
    environment:
      - S6_KEEP_ENV=1
      - YII_ENV=\${YII_ENV:-prod}
      - YII_DEBUG=\${YII_DEBUG:-false}
      - DB_HOST=db
      - DB_USER=\${DB_USER:-ildis}
      - DB_PASSWORD=\${DB_PASSWORD}
      - DB_DATABASE=\${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=\${DB_DATABASE_PORT:-3306}
      - PUBLIC_DOMAIN=\${PUBLIC_DOMAIN:-http://localhost:8080}
      - COOKIE_VALIDATION_KEY_BE=\${COOKIE_VALIDATION_KEY_BE}
      - COOKIE_VALIDATION_KEY_FE=\${COOKIE_VALIDATION_KEY_FE}
      - RECAPTCHA_SITE_KEY=\${RECAPTCHA_SITE_KEY:-}
      - RECAPTCHA_SECRET_KEY=\${RECAPTCHA_SECRET_KEY:-}
      - PHP_DISPLAY_ERRORS=Off
      - PHP_ERROR_REPORTING=E_ALL & ~E_DEPRECATED
    healthcheck:
      test: [ "CMD", "curl", "-f", "http://localhost/" ]
      interval: 30s
      timeout: 10s
      retries: 3

  cron:
    image: ghcr.io/bphndigitalservice/ildis-cron:\${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_cron
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - feed_data:/var/www/feed
    environment:
      - YII_ENV=\${YII_ENV:-prod}
      - YII_DEBUG=\${YII_DEBUG:-false}
      - DB_HOST=db
      - DB_USER=\${DB_USER:-ildis}
      - DB_PASSWORD=\${DB_PASSWORD}
      - DB_DATABASE=\${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=\${DB_DATABASE_PORT:-3306}

volumes:
  mysql_data:
  runtime:
  backend-assets:
  frontend-assets:
  backend-uploads:
  frontend-uploads:
  feed_data:
EOF
    fi

    success "${COMPOSE_FILE} generated"
}