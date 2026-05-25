# One-Click Install Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a `curl | bash` one-click install script for ILDIS that handles fresh install, updates, and three database modes (MariaDB, MySQL, external).

**Architecture:** Single `install.sh` script stored in the repo root. Generates `.env` and `docker-compose.yml` from heredocs, pulling pre-built GHCR images. Interactive wizard for configuration, with `--non-interactive` mode for CI. Existing `update.sh` becomes a thin wrapper.

**Tech Stack:** Bash 4+, Docker Compose v2, GHCR container images

---

### Task 1: Create install.sh — Constants, Helpers, and Prerequisites

**Files:**
- Create: `install.sh`

**Step 1: Create install.sh with shebang, constants, and helper functions**

```bash
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
```

**Step 2: Add argument parsing and mode detection**

Append to `install.sh`:

```bash
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
```

**Step 3: Add prerequisite checks**

Append to `install.sh`:

```bash
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
```

**Step 4: Verify the script runs without error**

Run: `bash -n install.sh`
Expected: No syntax errors

**Step 5: Commit**

```bash
git add install.sh
git commit -m "feat: add install.sh with constants, helpers, and prerequisite checks"
```

---

### Task 2: Add Interactive Wizard Functions

**Files:**
- Modify: `install.sh`

**Step 1: Add the interactive wizard function**

Append to `install.sh`:

```bash
# ── Interactive wizard ───────────────────────────────────────────────────────
run_wizard() {
    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║        ILDIS Installation Wizard         ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
    echo ""

    # Install directory
    INSTALL_DIR=$(prompt_value "Install directory" "${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}")
    echo ""

    # App port
    PORT=$(prompt_value "App port" "${PORT:-${DEFAULT_PORT}}")
    echo ""

    # Public domain
    PUBLIC_DOMAIN=$(prompt_value "Public domain URL" "${PUBLIC_DOMAIN:-http://localhost:${PORT}}")
    echo ""

    # Database type
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

    # reCAPTCHA (optional)
    echo -e "${BOLD}reCAPTCHA (optional — press Enter to skip):${NC}"
    RECAPTCHA_SITE_KEY=$(prompt_value "  reCAPTCHA site key" "${RECAPTCHA_SITE_KEY:-}")
    if [ -n "${RECAPTCHA_SITE_KEY}" ]; then
        RECAPTCHA_SECRET_KEY=$(prompt_value "  reCAPTCHA secret key" "${RECAPTCHA_SECRET_KEY:-}")
    else
        RECAPTCHA_SECRET_KEY=""
    fi
    echo ""

    # Cookie keys (always auto-generated)
    COOKIE_VALIDATION_KEY_BE="${COOKIE_VALIDATION_KEY_BE:-$(generate_random_key)}"
    COOKIE_VALIDATION_KEY_FE="${COOKIE_VALIDATION_KEY_FE:-$(generate_random_key)}"

    # Yii environment
    YII_ENV="${YII_ENV:-prod}"
    YII_DEBUG="${YII_DEBUG:-false}"

    # Summary
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
```

**Step 2: Verify syntax**

Run: `bash -n install.sh`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add install.sh
git commit -m "feat: add interactive wizard to install.sh"
```

---

### Task 3: Add `.env` and `docker-compose.yml` Generation

**Files:**
- Modify: `install.sh`

**Step 1: Add generate_env function**

Append to `install.sh`:

```bash
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
```

**Step 2: Add generate_compose function**

This is the core — it generates the production docker-compose.yml conditionally based on DB_TYPE.

Append to `install.sh`:

```bash
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

    local ILDIS_TAG="${ILDIS_IMAGE_TAG:-latest}"

    if [ "${DB_TYPE}" = "external" ]; then
        cat > "${INSTALL_DIR}/${COMPOSE_FILE}" <<EOF
services:
  app:
    image: ${GHCR_IMAGE}:\${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_app
    restart: unless-stopped
    ports:
      - "\${PORT:-${DEFAULT_PORT}}:80"
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
      - DB_HOST=\${DB_HOST}
      - DB_USER=\${DB_USER}
      - DB_PASSWORD=\${DB_PASSWORD}
      - DB_DATABASE=\${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=\${DB_DATABASE_PORT:-3306}
      - PUBLIC_DOMAIN=\${PUBLIC_DOMAIN:-http://localhost:${DEFAULT_PORT}}
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
    image: ${GHCR_IMAGE}-cron:\${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_cron
    restart: unless-stopped
    volumes:
      - feed_data:/var/www/feed
    environment:
      - YII_ENV=\${YII_ENV:-prod}
      - YII_DEBUG=\${YII_DEBUG:-false}
      - DB_HOST=\${DB_HOST}
      - DB_USER=\${DB_USER}
      - DB_PASSWORD=\${DB_PASSWORD}
      - DB_DATABASE=\${DB_DATABASE:-ildis_v4}
      - DB_DATABASE_PORT=\${DB_DATABASE_PORT:-3306}

volumes:
  runtime:
  backend-assets:
  frontend-assets:
  backend-uploads:
  frontend-uploads:
  feed_data:
EOF
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
    image: ${GHCR_IMAGE}:\${ILDIS_IMAGE_TAG:-latest}
    container_name: ildis_app
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "\${PORT:-${DEFAULT_PORT}}:80"
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
      - PUBLIC_DOMAIN=\${PUBLIC_DOMAIN:-http://localhost:${DEFAULT_PORT}}
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
    image: ${GHCR_IMAGE}-cron:\${ILDIS_IMAGE_TAG:-latest}
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
```

**Step 3: Verify syntax**

Run: `bash -n install.sh`
Expected: No syntax errors

**Step 4: Commit**

```bash
git add install.sh
git commit -m "feat: add .env and docker-compose.yml generation to install.sh"
```

---

### Task 4: Add Install, Update, and Main Functions

**Files:**
- Modify: `install.sh`

**Step 1: Add do_install function**

Append to `install.sh`:

```bash
# ── Install ──────────────────────────────────────────────────────────────────
do_install() {
    mkdir -p "${INSTALL_DIR}"

    generate_env
    generate_compose

    info "Pulling ILDIS Docker images..."
    if ! docker compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" --env-file "${INSTALL_DIR}/${ENV_FILE}" pull 2>&1; then
        fail "Failed to pull Docker images. Check your network connection."
    fi
    success "Docker images pulled"

    info "Starting ILDIS..."
    if ! docker compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" --env-file "${INSTALL_DIR}/${ENV_FILE}" up -d 2>&1; then
        fail "Failed to start containers. Check logs: docker compose -f ${INSTALL_DIR}/${COMPOSE_FILE} logs"
    fi

    # Wait for database if internal
    if [ "${DB_TYPE}" != "external" ]; then
        info "Waiting for database to be ready..."
        local db_ready=false
        for i in $(seq 1 "${MYSQL_HEALTH_RETRIES}"); do
            if docker compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" exec -T db healthcheck.sh --connect --innodb_initialized &>/dev/null 2>&1 || \
               docker compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" exec -T db mysqladmin ping -h localhost -u root &>/dev/null 2>&1; then
                db_ready=true
                break
            fi
            info "Database not ready, retrying... ($i/${MYSQL_HEALTH_RETRIES})"
            sleep "${MYSQL_HEALTH_INTERVAL}"
        done
        if [ "${db_ready}" = false ]; then
            warn "Database did not become ready in time. Containers may need manual intervention."
        else
            success "Database is ready"
        fi
    fi

    # Wait for app
    local app_port="${PORT:-${DEFAULT_PORT}}"
    info "Waiting for ILDIS application on port ${app_port}..."
    local app_ready=false
    for i in $(seq 1 "${HEALTH_RETRIES}"); do
        if curl -sf "http://localhost:${app_port}/" >/dev/null 2>&1; then
            app_ready=true
            break
        fi
        info "Application not ready, retrying... ($i/${HEALTH_RETRIES})"
        sleep "${HEALTH_INTERVAL}"
    done

    if [ "${app_ready}" = false ]; then
        echo ""
        echo -e "${YELLOW}ILDIS containers are running but the app is not responding yet.${NC}"
        echo "This may take a minute. Check status with:"
        echo "  docker compose -f ${INSTALL_DIR}/${COMPOSE_FILE} logs app"
        echo ""
        echo "Once ready, visit: http://localhost:${app_port}"
    else
        success "ILDIS is responding"
    fi

    # Run migrations
    info "Running database migrations..."
    if docker compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" exec -T app php yii migrate/up --interactive=0 --migrationPath=@console/migrations 2>&1; then
        success "Database migrations applied"
    else
        warn "Migration command returned non-zero. This may be normal if no migrations are pending."
    fi

    # Print success
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║      ILDIS Installed Successfully!        ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo "  URL:         http://localhost:${app_port}"
    echo "  Directory:   ${INSTALL_DIR}"
    echo "  Config:      ${INSTALL_DIR}/${ENV_FILE}"
    echo "  Compose:     ${INSTALL_DIR}/${COMPOSE_FILE}"
    echo ""
    echo "  Useful commands:"
    echo "    docker compose -f ${INSTALL_DIR}/${COMPOSE_FILE} logs -f     # Follow logs"
    echo "    docker compose -f ${INSTALL_DIR}/${COMPOSE_FILE} down        # Stop containers"
    echo "    docker compose -f ${INSTALL_DIR}/${COMPOSE_FILE} pull        # Update images"
    echo ""
    echo -e "  ${CYAN}To update ILDIS later, run: ./install.sh --update${NC}"
    echo ""
}
```

**Step 2: Add do_update function**

Append to `install.sh`:

```bash
# ── Update ───────────────────────────────────────────────────────────────────
do_update() {
    local compose_file="${INSTALL_DIR}/${COMPOSE_FILE}"
    local env_file="${INSTALL_DIR}/${ENV_FILE}"

    if [ ! -f "${compose_file}" ] || [ ! -f "${env_file}" ]; then
        fail "No existing ILDIS installation found in ${INSTALL_DIR}. Run ./install.sh without --update for a fresh install."
    fi

    # Source existing .env
    info "Loading existing configuration..."
    while IFS='=' read -r key value; do
        case "$key" in
            ''|\#*) continue ;;
        esac
        [[ "$key" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] && export "$key=$value"
    done < "${env_file}"

    # Get versions
    local current_version="unknown"
    if [ -f "${INSTALL_DIR}/${VERSION_FILE}" ]; then
        current_version=$(tr -d '[:space:]' < "${INSTALL_DIR}/${VERSION_FILE}")
    fi

    local latest_version="${ILDIS_IMAGE_TAG:-latest}"
    if [ "${latest_version}" = "latest" ]; then
        info "Checking for latest version..."
        if command -v curl &>/dev/null; then
            local release_info
            release_info=$(curl -sf "https://api.github.com/repos/${GITHUB_REPO}/releases/latest" 2>/dev/null || echo "{}")
            latest_version=$(echo "${release_info}" | grep -o '"tag_name":"[^"]*"' | head -1 | sed 's/"tag_name":"//;s/"$//')
            [ -z "${latest_version}" ] && latest_version="latest"
        fi
    fi

    echo ""
    echo -e "${BOLD}ILDIS Update${NC}"
    echo "  Current version: ${current_version}"
    echo "  Target version:  ${latest_version}"
    echo ""

    if [ "${NON_INTERACTIVE}" = false ]; then
        if ! confirm "Proceed with update?" "y"; then
            echo "Update cancelled."
            exit 0
        fi
    fi

    # Database backup
    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="${INSTALL_DIR}/${BACKUP_DIR}/ildis_${timestamp}.sql.gz"
    mkdir -p "${INSTALL_DIR}/${BACKUP_DIR}"

    info "Creating database backup..."
    local db_host="${DB_HOST:-db}"
    local db_user="${DB_USER:-root}"
    local db_pass="${DB_PASSWORD:-}"
    local db_name="${DB_DATABASE:-ildis_v4}"
    local db_port="${DB_DATABASE_PORT:-3306}"

    local backup_success=false
    if docker compose -f "${compose_file}" exec -T db sh -c \
        "MYSQL_PWD=\"${db_pass}\" mysqldump -h localhost -u \"${db_user}\" -P \"${db_port}\" --single-transaction --routines --triggers \"${db_name}\"" 2>/dev/null | gzip > "${backup_file}"; then
        backup_success=true
    fi

    if [ "${backup_success}" = true ]; then
        success "Database backup saved: ${backup_file} ($(du -h "${backup_file}" | cut -f1))"
    else
        warn "Database backup failed. Continuing without backup."
        warn "You can create a manual backup with:"
        warn "  docker compose -f ${compose_file} exec -T db mysqldump ..."
        if [ "${NON_INTERACTIVE}" = false ]; then
            if ! confirm "Continue without backup?" "n"; then
                echo "Update cancelled."
                exit 0
            fi
        fi
    fi

    # Pull new images
    info "Pulling updated Docker images..."
    if ! docker compose -f "${compose_file}" --env-file "${env_file}" pull 2>&1; then
        fail "Failed to pull Docker images."
    fi
    success "Images updated"

    # Restart
    info "Restarting ILDIS containers..."
    if ! docker compose -f "${compose_file}" --env-file "${env_file}" up -d 2>&1; then
        fail "Failed to restart containers."
    fi

    # Wait for health
    local app_port="${PORT:-${DEFAULT_PORT}}"
    info "Waiting for application on port ${app_port}..."

    if [ "${DB_TYPE:-mariadb}" != "external" ]; then
        info "Waiting for database..."
        local db_ready=false
        for i in $(seq 1 "${MYSQL_HEALTH_RETRIES}"); do
            if docker compose -f "${compose_file}" exec -T db healthcheck.sh --connect --innodb_initialized &>/dev/null 2>&1 || \
               docker compose -f "${compose_file}" exec -T db mysqladmin ping -h localhost &>/dev/null 2>&1; then
                db_ready=true
                break
            fi
            sleep "${MYSQL_HEALTH_INTERVAL}"
        done
        [ "${db_ready}" = true ] && success "Database is ready" || warn "Database not ready yet"
    fi

    local app_ready=false
    for i in $(seq 1 "${HEALTH_RETRIES}"); do
        if curl -sf "http://localhost:${app_port}/" >/dev/null 2>&1; then
            app_ready=true
            break
        fi
        sleep "${HEALTH_INTERVAL}"
    done

    if [ "${app_ready}" = false ]; then
        echo ""
        echo -e "${YELLOW}Application not responding after update.${NC}"
        echo "Check logs: docker compose -f ${compose_file} logs app"
    else
        success "Application is responding"
    fi

    # Run migrations
    info "Running database migrations..."
    if docker compose -f "${compose_file}" exec -T app php yii migrate/up --interactive=0 --migrationPath=@console/migrations 2>&1; then
        success "Migrations applied"
    else
        warn "Migration command returned non-zero. May be normal if none pending."
    fi

    # Update version file
    echo "${latest_version}" > "${INSTALL_DIR}/${VERSION_FILE}"

    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║        ILDIS Updated Successfully!       ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo "  Previous version: ${current_version}"
    echo "  New version:       ${latest_version}"
    echo "  Backup file:       ${backup_file}"
    echo "  URL:               http://localhost:${app_port}"
    echo ""
}
```

**Step 3: Add detect_existing_install and main functions**

Append to `install.sh`:

```bash
# ── Detect existing installation ─────────────────────────────────────────────
detect_existing_install() {
    local dir="${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}"
    if [ -f "${dir}/${COMPOSE_FILE}" ] && [ -f "${dir}/${ENV_FILE}" ]; then
        echo "${dir}"
        return 0
    fi
    # Check current directory
    if [ -f "${COMPOSE_FILE}" ] && [ -f "${ENV_FILE}" ]; then
        echo "$(pwd)"
        return 0
    fi
    return 1
}

# ── Main ────────────────────────────────────────────────────────────────────
main() {
    echo ""
    echo -e "${BOLD}ILDIS — Indonesian Law Documentation Information System${NC}"
    echo ""

    # If update mode, find existing install
    if [ "${ACTION}" = "update" ]; then
        local existing_dir
        existing_dir=$(detect_existing_install) || true
        if [ -n "${existing_dir}" ]; then
            INSTALL_DIR="${existing_dir}"
        elif [ -z "${INSTALL_DIR}" ]; then
            INSTALL_DIR="${DEFAULT_INSTALL_DIR}"
        fi
        info "Updating ILDIS in ${INSTALL_DIR}"
        do_update
        return
    fi

    # Fresh install
    check_prerequisites

    # Set defaults for non-interactive mode
    if [ "${NON_INTERACTIVE}" = true ]; then
        INSTALL_DIR="${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}"
        PORT="${PORT:-${DEFAULT_PORT}}"
        PUBLIC_DOMAIN="${PUBLIC_DOMAIN:-http://localhost:${PORT}}"
        DB_TYPE="${DB_TYPE_OVERRIDE:-mariadb}"
        DB_USER="${DB_USER:-ildis}"
        DB_DATABASE="${DB_DATABASE:-ildis_v4}"
        DB_DATABASE_PORT="${DB_DATABASE_PORT:-3306}"
        DB_PASSWORD="${DB_PASSWORD:-$(generate_random_key)}"
        DB_HOST="${DB_HOST:-mysql}"
        YII_ENV="${YII_ENV:-prod}"
        YII_DEBUG="${YII_DEBUG:-false}"
        COOKIE_VALIDATION_KEY_BE="${COOKIE_VALIDATION_KEY_BE:-$(generate_random_key)}"
        COOKIE_VALIDATION_KEY_FE="${COOKIE_VALIDATION_KEY_FE:-$(generate_random_key)}"
        RECAPTCHA_SITE_KEY="${RECAPTCHA_SITE_KEY:-}"
        RECAPTCHA_SECRET_KEY="${RECAPTCHA_SECRET_KEY:-}"
    else
        run_wizard
    fi

    do_install
}

main "$@"
```

**Step 4: Verify syntax and make executable**

Run:
```bash
bash -n install.sh && chmod +x install.sh
```
Expected: No syntax errors

**Step 5: Commit**

```bash
git add install.sh
git commit -m "feat: add install, update, and main functions to install.sh"
```

---

### Task 5: Update `update.sh` to be a Thin Wrapper

**Files:**
- Modify: `update.sh`

**Step 1: Replace update.sh with thin wrapper**

Replace the entire content of `update.sh` with:

```bash
#!/usr/bin/env bash
#
# ILDIS Update Script (wrapper)
# This script has been replaced by install.sh --update
#

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -f "${SCRIPT_DIR}/install.sh" ]; then
    exec "${SCRIPT_DIR}/install.sh" --update "$@"
else
    echo "ERROR: install.sh not found in ${SCRIPT_DIR}"
    echo "Please download the latest release from:"
    echo "  https://github.com/bphndigitalservice/ildis"
    exit 1
fi
```

**Step 2: Verify syntax**

Run: `bash -n update.sh`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add update.sh
git commit -m "refactor: update.sh becomes thin wrapper for install.sh --update"
```

---

### Task 6: Add GitHub Actions Workflow for GHCR Image Publishing

**Files:**
- Create: `.github/workflows/docker-publish.yml`

The install script depends on pre-built images at `ghcr.io/bphndigitalservice/ildis` and `ghcr.io/bphndigitalservice/ildis-cron`. We need a CI workflow to publish these.

**Step 1: Create the workflow file**

```yaml
name: Publish Docker Images

on:
  push:
    branches: [main]
    tags: ['v*']
  workflow_dispatch:

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  publish:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push app image
        uses: docker/build-push-action@v6
        with:
          context: .
          file: ./Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: Build and push cron image
        uses: docker/build-push-action@v6
        with:
          context: .
          file: ./Dockerfile.cron
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

**Wait** — the cron image needs a different tag suffix. Let me fix this.

Replace the workflow with two separate jobs, one for each image:

```yaml
name: Publish Docker Images

on:
  push:
    branches: [main]
    tags: ['v*']
  workflow_dispatch:

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  publish-app:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata (app)
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push app image
        uses: docker/build-push-action@v6
        with:
          context: .
          file: ./Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  publish-cron:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata (cron)
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}-cron
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push cron image
        uses: docker/build-push-action@v6
        with:
          context: .
          file: ./Dockerfile.cron
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

**Step 2: Commit**

```bash
git add .github/workflows/docker-publish.yml
git commit -m "ci: add GitHub Actions workflow for publishing GHCR images"
```

---

### Task 7: Manual Testing Checklist

This is an ops/deployment script — no automated tests. Verify manually:

**Step 1: Syntax check**

```bash
bash -n install.sh
```

**Step 2: Dry-run help**

```bash
./install.sh --help
```

**Step 3: Test on a clean Docker environment**

- Fresh install with MariaDB (default)
- Fresh install with MySQL
- Fresh install with external DB
- `--non-interactive` mode with env vars
- Update from existing install

**Step 4: Commit any fixes**

If manual testing reveals issues, fix them and commit.

---

### Task 8: Update README with Install Instructions

**Files:**
- Modify: `README.md`

**Step 1: Add install section to README**

Add a "Quick Install" section near the top of the README (after the title/description):

```markdown
## Quick Install

Install ILDIS with a single command (requires [Docker](https://docs.docker.com/engine/install/) and [Docker Compose](https://docs.docker.com/compose/install/)):

```bash
curl -fsSL https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh | bash
```

For non-interactive installs:

```bash
curl -fsSL https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh | bash -s -- --non-interactive
```

To update an existing installation:

```bash
./install.sh --update
```
```

**Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add quick install instructions to README"
```