#!/usr/bin/env bash
#
# ILDIS Pasang Sekali Klik
# Pasang ILDIS dengan satu perintah:
#   curl -fsSL https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh | bash
#
# Penggunaan:
#   ./install.sh                  Pasang interaktif
#   ./install.sh --non-interactive  Gunakan env var, tanpa prompt
#   ./install.sh --update          Perbarui instalasi yang ada
#   ./install.sh --help            Tampilkan bantuan

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

COMPOSE_CMD=""
CONTAINER_RUNTIME=""

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
warn()    { echo -e "${YELLOW}[PERINGATAN]${NC} $*"; }
fail()    { echo -e "${RED}[GAGAL]${NC} $*"; exit 1; }

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

# ── Compose command wrapper ──────────────────────────────────────────────────
# Normalizes differences between docker compose, podman compose, and podman-compose.
run_compose() {
    if [ "${COMPOSE_CMD}" = "podman-compose" ]; then
        # podman-compose: load env manually, no --env-file flag
        set -a
        # shellcheck disable=SC1090
        [ -f "${INSTALL_DIR}/${ENV_FILE}" ] && source "${INSTALL_DIR}/${ENV_FILE}" 2>/dev/null || true
        set +a
        podman-compose -f "${INSTALL_DIR}/${COMPOSE_FILE}" "$@"
    else
        # docker compose and podman compose: use --env-file flag
        ${COMPOSE_CMD} -f "${INSTALL_DIR}/${COMPOSE_FILE}" --env-file "${INSTALL_DIR}/${ENV_FILE}" "$@"
    fi
}

run_compose_update() {
    local compose_file="${1}"
    local env_file="${2}"
    shift 2
    if [ "${COMPOSE_CMD}" = "podman-compose" ]; then
        set -a
        # shellcheck disable=SC1090
        [ -f "${env_file}" ] && source "${env_file}" 2>/dev/null || true
        set +a
        podman-compose -f "${compose_file}" "$@"
    else
        ${COMPOSE_CMD} -f "${compose_file}" --env-file "${env_file}" "$@"
    fi
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
            echo "Opsi tidak dikenal: $1"
            echo "Jalankan './install.sh --help' untuk penggunaan."
            exit 1
            ;;
    esac
done

show_help() {
    cat <<'EOF'
ILDIS Pasang Sekali Klik — Pasang ILDIS dengan Docker/Podman

Penggunaan:
  ./install.sh                      Pasang interaktif
  ./install.sh --non-interactive    Gunakan env var, tanpa prompt
  ./install.sh --update             Perbarui instalasi yang ada
  ./install.sh --dir /opt/ildis    Tentukan direktori instalasi
  ./install.sh --db-type mariadb   Atur tipe DB (mariadb|mysql|external)
  ./install.sh --help               Tampilkan bantuan ini

Container runtime:
  Docker Compose (docker compose) — dideteksi otomatis
  Podman Compose (podman compose) — dideteksi otomatis
  podman-compose                   — dideteksi otomatis

Variabel lingkungan (untuk --non-interactive):
  INSTALL_DIR        Direktori instalasi (bawaan: /opt/ildis)
  PORT               Port aplikasi (bawaan: 8080)
  PUBLIC_DOMAIN      URL publik (bawaan: http://localhost:8080)
  DB_TYPE            Tipe database: mariadb, mysql, external
  DB_HOST            Host database (untuk external)
  DB_USER            Pengguna database
  DB_PASSWORD        Kata sandi database
  DB_DATABASE        Nama database (bawaan: ildis_v4)
  DB_DATABASE_PORT   Port database (bawaan: 3306)
  RECAPTCHA_SITE_KEY    Kunci situs reCAPTCHA
  RECAPTCHA_SECRET_KEY  Kunci rahasia reCAPTCHA

Contoh:
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
    info "Memeriksa prasyarat..."

    COMPOSE_CMD=""
    CONTAINER_RUNTIME=""
    if command -v docker &>/dev/null && docker compose version &>/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
        CONTAINER_RUNTIME="docker"
        success "Docker Compose ditemukan"
    elif command -v podman &>/dev/null; then
        if podman compose version &>/dev/null 2>&1; then
            COMPOSE_CMD="podman compose"
            CONTAINER_RUNTIME="podman"
            success "Podman Compose ditemukan"
        elif command -v podman-compose &>/dev/null; then
            COMPOSE_CMD="podman-compose"
            CONTAINER_RUNTIME="podman"
            success "podman-compose ditemukan"
        fi
    fi

    if [ -z "${COMPOSE_CMD}" ]; then
        echo ""
        echo -e "${RED}Tidak ditemukan Docker Compose maupun Podman Compose.${NC}"
        echo ""
        echo "Pasang salah satu:"
        echo "  Docker:       https://docs.docker.com/engine/install/"
        echo "  Podman:       https://podman.io/getting-started/installation"
        echo "  podman-compose: pip install podman-compose"
        fail "Docker Compose atau Podman Compose diperlukan."
    fi

    local available_kb available_mb
    available_kb=$(df -k . | awk 'NR==2 {print $4}')
    available_mb=$((available_kb / 1024))
    if [ "${available_mb}" -lt "${MIN_DISK_MB}" ]; then
        fail "Ruang disk tidak cukup: ${available_mb}MB tersedia, ${MIN_DISK_MB}MB diperlukan."
    fi
    success "Ruang disk: ${available_mb}MB tersedia"

    local port="${PORT:-${DEFAULT_PORT}}"
    if command -v ss &>/dev/null; then
        if ss -tlnp 2>/dev/null | grep -q ":${port} "; then
            warn "Port ${port} tampaknya sedang digunakan."
            warn "Anda dapat mengubahnya dengan PORT= saat menjalankan skrip."
        fi
    elif command -v lsof &>/dev/null; then
        if lsof -i ":${port}" &>/dev/null 2>&1; then
            warn "Port ${port} tampaknya sedang digunakan."
        fi
    fi

    success "Prasyarat OK (${CONTAINER_RUNTIME})"
}

# ── Interactive wizard ───────────────────────────────────────────────────────
run_wizard() {
    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║        Panduan Instalasi ILDIS            ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
    echo ""

    INSTALL_DIR=$(prompt_value "Direktori instalasi" "${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}")
    echo ""

    PORT=$(prompt_value "Port aplikasi" "${PORT:-${DEFAULT_PORT}}")
    echo ""

    PUBLIC_DOMAIN=$(prompt_value "URL domain publik" "${PUBLIC_DOMAIN:-http://localhost:${PORT}}")
    echo ""

    echo -e "${BOLD}Konfigurasi database:${NC}"
    echo "  1) MariaDB 10.11 (disarankan, bawaan)"
    echo "  2) MySQL 8.0"
    echo "  3) Database eksternal"
    echo ""
    if [ -n "${DB_TYPE_OVERRIDE}" ]; then
        DB_TYPE="${DB_TYPE_OVERRIDE}"
    else
        local db_choice
        db_choice=$(prompt_value "Pilih tipe database (1/2/3)" "1")
        case "$db_choice" in
            2) DB_TYPE="mysql" ;;
            3) DB_TYPE="external" ;;
            *) DB_TYPE="mariadb" ;;
        esac
    fi

    DB_PASSWORD="${DB_PASSWORD:-}"
    if [ "${DB_TYPE}" = "external" ]; then
        echo ""
        echo -e "${CYAN}Database eksternal dipilih. Masukkan detail koneksi:${NC}"
        DB_HOST=$(prompt_value "  Host database" "${DB_HOST:-}")
        DB_DATABASE_PORT=$(prompt_value "  Port database" "${DB_DATABASE_PORT:-3306}")
        DB_USER=$(prompt_value "  Pengguna database" "${DB_USER:-ildis}")
        DB_DATABASE=$(prompt_value "  Nama database" "${DB_DATABASE:-ildis_v4}")
        DB_PASSWORD=$(prompt_value "  Kata sandi database" "" "true")
        if [ -z "${DB_PASSWORD}" ]; then
            fail "Kata sandi database diperlukan untuk database eksternal."
        fi
    else
        local db_label="MariaDB 10.11"
        [ "${DB_TYPE}" = "mysql" ] && db_label="MySQL 8.0"
        echo ""
        echo -e "${CYAN}${db_label} dipilih.${NC}"

        if [ -z "${DB_PASSWORD}" ]; then
            DB_PASSWORD=$(generate_random_key)
            echo -e "  Kata sandi database dibuat: ${YELLOW}${DB_PASSWORD}${NC}"
            echo "  Simpan kata sandi ini di tempat yang aman!"
        fi
        DB_USER=$(prompt_value "  Pengguna database" "${DB_USER:-ildis}")
        DB_DATABASE=$(prompt_value "  Nama database" "${DB_DATABASE:-ildis_v4}")
    fi
    echo ""

    echo -e "${BOLD}reCAPTCHA (opsional — tekan Enter untuk lewati):${NC}"
    RECAPTCHA_SITE_KEY=$(prompt_value "  Kunci situs reCAPTCHA" "${RECAPTCHA_SITE_KEY:-}")
    if [ -n "${RECAPTCHA_SITE_KEY}" ]; then
        RECAPTCHA_SECRET_KEY=$(prompt_value "  Kunci rahasia reCAPTCHA" "${RECAPTCHA_SECRET_KEY:-}")
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
    echo -e "${BOLD}Ringkasan Instalasi:${NC}"
    echo -e "${BOLD}════════════════════════════════════════════${NC}"
    echo "  Direktori:   ${INSTALL_DIR}"
    echo "  Port:        ${PORT}"
    echo "  Domain:      ${PUBLIC_DOMAIN}"
    echo "  Database:    ${DB_TYPE}"
    if [ "${DB_TYPE}" = "external" ]; then
        echo "  Host DB:     ${DB_HOST}:${DB_DATABASE_PORT}"
    fi
    echo "  Pengguna DB: ${DB_USER}"
    echo "  Nama DB:     ${DB_DATABASE}"
    echo "  reCAPTCHA:   $([ -n "${RECAPTCHA_SITE_KEY}" ] && echo "terkonfigurasi" || echo "dilewati")"
    echo ""

    if [ "${NON_INTERACTIVE}" = false ]; then
        if ! confirm "Lanjutkan instalasi?" "y"; then
            echo "Instalasi dibatalkan."
            exit 0
        fi
    fi
}

# ── Generate .env file ──────────────────────────────────────────────────────
generate_env() {
    info "Membuat ${ENV_FILE}..."

    cat > "${INSTALL_DIR}/${ENV_FILE}" <<EOF
# Konfigurasi Lingkungan ILDIS
# Dibuat oleh install.sh pada $(date -Iseconds 2>/dev/null || date)
# Periksa dan perbarui sesuai kebutuhan.

# ── Aplikasi ──
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

# ── Validasi cookie (dibuat otomatis) ──
COOKIE_VALIDATION_KEY_BE=${COOKIE_VALIDATION_KEY_BE}
COOKIE_VALIDATION_KEY_FE=${COOKIE_VALIDATION_KEY_FE}

# ── reCAPTCHA (opsional) ──
RECAPTCHA_SITE_KEY=${RECAPTCHA_SITE_KEY:-}
RECAPTCHA_SECRET_KEY=${RECAPTCHA_SECRET_KEY:-}

# ── Tag image kontainer ──
ILDIS_IMAGE_TAG=${ILDIS_IMAGE_TAG:-latest}
EOF

    chmod 600 "${INSTALL_DIR}/${ENV_FILE}"
    success "${ENV_FILE} dibuat"
}

# ── Generate docker-compose.yml ─────────────────────────────────────────────
generate_compose() {
    info "Membuat ${COMPOSE_FILE}..."

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

    success "${COMPOSE_FILE} dibuat"
}

# ── Install ──────────────────────────────────────────────────────────────────
do_install() {
    mkdir -p "${INSTALL_DIR}"

    generate_env
    generate_compose

    info "Mengunduh image ILDIS..."
    if ! run_compose pull 2>&1; then
        fail "Gagal mengunduh image. Periksa koneksi jaringan dan pastikan image tersedia di ${GHCR_IMAGE}."
    fi
    success "Image berhasil diunduh"

    info "Memulai ILDIS..."
    if ! run_compose up -d 2>&1; then
        fail "Gagal memulai container. Periksa log: ${COMPOSE_CMD} -f ${INSTALL_DIR}/${COMPOSE_FILE} logs"
    fi

    if [ "${DB_TYPE}" != "external" ]; then
        info "Menunggu database siap..."
        local db_ready=false
        for i in $(seq 1 "${MYSQL_HEALTH_RETRIES}"); do
            if run_compose exec -T db healthcheck.sh --connect --innodb_initialized &>/dev/null 2>&1 || \
               run_compose exec -T db mysqladmin ping -h localhost -u root &>/dev/null 2>&1; then
                db_ready=true
                break
            fi
            info "Database belum siap, mencoba ulang... ($i/${MYSQL_HEALTH_RETRIES})"
            sleep "${MYSQL_HEALTH_INTERVAL}"
        done
        if [ "${db_ready}" = false ]; then
            warn "Database belum siap dalam waktu yang ditentukan. Container mungkin perlu penanganan manual."
        else
            success "Database siap"
        fi
    fi

    local app_port="${PORT:-${DEFAULT_PORT}}"
    info "Menunggu aplikasi ILDIS di port ${app_port}..."
    local app_ready=false
    for i in $(seq 1 "${HEALTH_RETRIES}"); do
        if curl -sf "http://localhost:${app_port}/" >/dev/null 2>&1; then
            app_ready=true
            break
        fi
        info "Aplikasi belum siap, mencoba ulang... ($i/${HEALTH_RETRIES})"
        sleep "${HEALTH_INTERVAL}"
    done

    if [ "${app_ready}" = false ]; then
        echo ""
        echo -e "${YELLOW}Container ILDIS berjalan tetapi aplikasi belum merespons.${NC}"
        echo "Ini mungkin membutuhkan beberapa saat. Periksa status dengan:"
        echo "  ${COMPOSE_CMD} -f ${INSTALL_DIR}/${COMPOSE_FILE} logs app"
        echo ""
        echo "Jika sudah siap, kunjungi: http://localhost:${app_port}"
    else
        success "ILDIS merespons"
    fi

    info "Menjalankan migrasi database..."
    if run_compose exec -T app php yii migrate/up --interactive=0 --migrationPath=@console/migrations 2>&1; then
        success "Migrasi database berhasil diterapkan"
    else
        warn "Perintah migrasi mengembalikan non-zero. Ini mungkin normal jika tidak ada migrasi tertunda."
    fi

    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║       ILDIS Berhasil Dipasang!            ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo "  URL:           http://localhost:${app_port}"
    echo "  Direktori:     ${INSTALL_DIR}"
    echo "  Konfigurasi:   ${INSTALL_DIR}/${ENV_FILE}"
    echo "  Compose:        ${INSTALL_DIR}/${COMPOSE_FILE}"
    echo ""
    echo "  Perintah berguna:"
    echo "    ${COMPOSE_CMD} -f ${INSTALL_DIR}/${COMPOSE_FILE} logs -f       # Ikuti log"
    echo "    ${COMPOSE_CMD} -f ${INSTALL_DIR}/${COMPOSE_FILE} down          # Hentikan container"
    echo "    ${COMPOSE_CMD} -f ${INSTALL_DIR}/${COMPOSE_FILE} pull          # Perbarui image"
    echo ""
    echo -e "  ${CYAN}Untuk memperbarui ILDIS, jalankan: ./install.sh --update${NC}"
    echo ""
}

# ── Update ───────────────────────────────────────────────────────────────────
do_update() {
    local compose_file="${INSTALL_DIR}/${COMPOSE_FILE}"
    local env_file="${INSTALL_DIR}/${ENV_FILE}"

    if [ ! -f "${compose_file}" ] || [ ! -f "${env_file}" ]; then
        fail "Instalasi ILDIS tidak ditemukan di ${INSTALL_DIR}. Jalankan ./install.sh tanpa --update untuk pemasangan baru."
    fi

    info "Memuat konfigurasi yang ada..."
    while IFS='=' read -r key value; do
        case "$key" in
            ''|\#*) continue ;;
        esac
        [[ "$key" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] && export "$key=$value"
    done < "${env_file}"

    local current_version="unknown"
    if [ -f "${INSTALL_DIR}/${VERSION_FILE}" ]; then
        current_version=$(tr -d '[:space:]' < "${INSTALL_DIR}/${VERSION_FILE}")
    fi

    local latest_version="${ILDIS_IMAGE_TAG:-latest}"
    if [ "${latest_version}" = "latest" ]; then
        info "Memeriksa versi terbaru..."
        if command -v curl &>/dev/null; then
            local release_info
            release_info=$(curl -sf "https://api.github.com/repos/${GITHUB_REPO}/releases/latest" 2>/dev/null || echo "{}")
            latest_version=$(echo "${release_info}" | grep -o '"tag_name":"[^"]*"' | head -1 | sed 's/"tag_name":"//;s/"$//')
            [ -z "${latest_version}" ] && latest_version="latest"
        fi
    fi

    echo ""
    echo -e "${BOLD}Pembaruan ILDIS${NC}"
    echo "  Versi saat ini: ${current_version}"
    echo "  Versi target:   ${latest_version}"
    echo ""

    if [ "${NON_INTERACTIVE}" = false ]; then
        if ! confirm "Lanjutkan pembaruan?" "y"; then
            echo "Pembaruan dibatalkan."
            exit 0
        fi
    fi

    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="${INSTALL_DIR}/${BACKUP_DIR}/ildis_${timestamp}.sql.gz"
    mkdir -p "${INSTALL_DIR}/${BACKUP_DIR}"

    info "Membuat cadangan database..."
    local db_host="${DB_HOST:-db}"
    local db_user="${DB_USER:-root}"
    local db_pass="${DB_PASSWORD:-}"
    local db_name="${DB_DATABASE:-ildis_v4}"
    local db_port="${DB_DATABASE_PORT:-3306}"

    local backup_success=false
    if [ "${DB_TYPE:-mariadb}" != "external" ]; then
        if ${COMPOSE_CMD} -f "${compose_file}" exec -T db sh -c \
            "MYSQL_PWD=\"${db_pass}\" mysqldump -h localhost -u \"${db_user}\" -P \"${db_port}\" --single-transaction --routines --triggers \"${db_name}\"" 2>/dev/null | gzip > "${backup_file}"; then
            backup_success=true
        fi
    else
        warn "Database eksternal terdeteksi — melewatkan cadangan otomatis."
        warn "Pastikan Anda memiliki cadangan sebelum melanjutkan."
    fi

    if [ "${backup_success}" = true ]; then
        success "Cadangan database disimpan: ${backup_file} ($(du -h "${backup_file}" | cut -f1))"
    elif [ "${DB_TYPE:-mariadb}" != "external" ]; then
        warn "Cadangan database gagal. Melanjutkan tanpa cadangan."
        warn "Anda dapat membuat cadangan manual dengan:"
        warn "  ${COMPOSE_CMD} -f ${compose_file} exec -T db mysqldump ..."
        if [ "${NON_INTERACTIVE}" = false ]; then
            if ! confirm "Lanjutkan tanpa cadangan?" "n"; then
                echo "Pembaruan dibatalkan."
                exit 0
            fi
        fi
    fi

    info "Mengunduh image terbaru..."
    if ! run_compose_update "${compose_file}" "${env_file}" pull 2>&1; then
        fail "Gagal mengunduh image."
    fi
    success "Image diperbarui"

    info "Memulai ulang container ILDIS..."
    if ! run_compose_update "${compose_file}" "${env_file}" up -d 2>&1; then
        fail "Gagal memulai ulang container."
    fi

    if [ "${DB_TYPE:-mariadb}" != "external" ]; then
        info "Menunggu database..."
        local db_ready=false
        for i in $(seq 1 "${MYSQL_HEALTH_RETRIES}"); do
            if run_compose_update "${compose_file}" "${env_file}" exec -T db healthcheck.sh --connect --innodb_initialized &>/dev/null 2>&1 || \
               run_compose_update "${compose_file}" "${env_file}" exec -T db mysqladmin ping -h localhost &>/dev/null 2>&1; then
                db_ready=true
                break
            fi
            sleep "${MYSQL_HEALTH_INTERVAL}"
        done
        [ "${db_ready}" = true ] && success "Database siap" || warn "Database belum siap"
    fi

    local app_port="${PORT:-${DEFAULT_PORT}}"
    info "Menunggu aplikasi di port ${app_port}..."

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
        echo -e "${YELLOW}Aplikasi tidak merespons setelah pembaruan.${NC}"
        echo "Periksa log: ${COMPOSE_CMD} -f ${compose_file} logs app"
    else
        success "Aplikasi merespons"
    fi

    info "Menjalankan migrasi database..."
    if run_compose_update "${compose_file}" "${env_file}" exec -T app php yii migrate/up --interactive=0 --migrationPath=@console/migrations 2>&1; then
        success "Migrasi diterapkan"
    else
        warn "Perintah migrasi mengembalikan non-zero. Mungkin normal jika tidak ada yang tertunda."
    fi

    echo "${latest_version}" > "${INSTALL_DIR}/${VERSION_FILE}"

    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║       ILDIS Berhasil Diperbarui!          ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
    echo ""
    echo "  Versi sebelumnya: ${current_version}"
    echo "  Versi baru:        ${latest_version}"
    if [ "${backup_success}" = true ]; then
        echo "  File cadangan:     ${backup_file}"
    fi
    echo "  URL:                http://localhost:${app_port}"
    echo ""
}

# ── Detect existing installation ─────────────────────────────────────────────
detect_existing_install() {
    local dir="${INSTALL_DIR:-${DEFAULT_INSTALL_DIR}}"
    if [ -f "${dir}/${COMPOSE_FILE}" ] && [ -f "${dir}/${ENV_FILE}" ]; then
        echo "${dir}"
        return 0
    fi
    if [ -f "${COMPOSE_FILE}" ] && [ -f "${ENV_FILE}" ]; then
        echo "$(pwd)"
        return 0
    fi
    return 1
}

# ── Main ────────────────────────────────────────────────────────────────────
main() {
    echo ""
    echo -e "${BOLD}ILDIS — Sistem Informasi Dokumentasi Hukum Indonesia${NC}"
    echo ""

    if [ "${ACTION}" = "update" ]; then
        # Detect container runtime for update mode
        COMPOSE_CMD=""
        CONTAINER_RUNTIME=""
        if command -v docker &>/dev/null && docker compose version &>/dev/null 2>&1; then
            COMPOSE_CMD="docker compose"
            CONTAINER_RUNTIME="docker"
        elif command -v podman &>/dev/null; then
            if podman compose version &>/dev/null 2>&1; then
                COMPOSE_CMD="podman compose"
                CONTAINER_RUNTIME="podman"
            elif command -v podman-compose &>/dev/null; then
                COMPOSE_CMD="podman-compose"
                CONTAINER_RUNTIME="podman"
            fi
        fi
        if [ -z "${COMPOSE_CMD}" ]; then
            fail "Tidak ditemukan Docker Compose maupun Podman Compose. Pasang salah satu sebelum memperbarui."
        fi

        local existing_dir
        existing_dir=$(detect_existing_install) || true
        if [ -n "${existing_dir}" ]; then
            INSTALL_DIR="${existing_dir}"
        elif [ -z "${INSTALL_DIR}" ]; then
            INSTALL_DIR="${DEFAULT_INSTALL_DIR}"
        fi
        info "Memperbarui ILDIS di ${INSTALL_DIR}"
        do_update
        return
    fi

    check_prerequisites

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