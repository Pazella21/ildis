#!/usr/bin/env bash
#
# Set safe ownership and permissions for an ILDIS (Yii2 practical template) install.
#
# Typical production usage (Apache + PHP-FPM on Ubuntu):
#   sudo APP_DIR=/mnt/data/ildis OWNER=deploy ./scripts/set-safe-permissions.sh
#
# Docker-style (everything runs as www-data):
#   sudo ./scripts/set-safe-permissions.sh --owner www-data
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

APP_DIR="${APP_DIR:-${DEFAULT_APP_DIR}}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
OWNER="${OWNER:-}"
DRY_RUN=0

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

usage() {
    cat <<'EOF'
Usage: set-safe-permissions.sh [options]

Apply safe ownership and permissions for ILDIS (Yii2 practical template).

Options:
  --app-dir PATH   Application root (default: parent of scripts/)
  --owner USER     File owner (default: current owner of app dir, or WEB_USER)
  --web-user USER  Web/PHP-FPM user (default: www-data)
  --web-group GRP  Web/PHP-FPM group (default: www-data)
  --dry-run        Print actions without changing anything
  -h, --help       Show this help

Environment variables:
  APP_DIR, OWNER, WEB_USER, WEB_GROUP

Examples:
  sudo APP_DIR=/mnt/data/ildis OWNER=deploy ./scripts/set-safe-permissions.sh
  sudo ./scripts/set-safe-permissions.sh --dry-run
EOF
}

log() {
    echo -e "${GREEN}==>${NC} $*"
}

warn() {
    echo -e "${YELLOW}!!>${NC} $*"
}

err() {
    echo -e "${RED}ERROR:${NC} $*" >&2
}

run() {
    if [ "${DRY_RUN}" -eq 1 ]; then
        echo "[dry-run] $*"
    else
        "$@"
    fi
}

require_root() {
    if [ "$(id -u)" -ne 0 ]; then
        err "This script must be run as root (use sudo)."
        exit 1
    fi
}

validate_app_dir() {
    if [ ! -d "${APP_DIR}" ]; then
        err "Application directory not found: ${APP_DIR}"
        exit 1
    fi

    if [ ! -f "${APP_DIR}/index.php" ] || [ ! -f "${APP_DIR}/yii" ]; then
        err "${APP_DIR} does not look like an ILDIS/Yii2 practical install (missing index.php or yii)."
        exit 1
    fi
}

resolve_owner() {
    if [ -n "${OWNER}" ]; then
        return
    fi

    OWNER="$(stat -c '%U' "${APP_DIR}" 2>/dev/null || stat -f '%Su' "${APP_DIR}")"
    if [ -z "${OWNER}" ]; then
        OWNER="${WEB_USER}"
    fi
}

ensure_user_exists() {
    if ! id "${WEB_USER}" >/dev/null 2>&1; then
        err "Web user does not exist: ${WEB_USER}"
        exit 1
    fi

    if ! getent group "${WEB_GROUP}" >/dev/null 2>&1; then
        err "Web group does not exist: ${WEB_GROUP}"
        exit 1
    fi

    if ! id "${OWNER}" >/dev/null 2>&1; then
        err "Owner user does not exist: ${OWNER}"
        exit 1
    fi
}

# Directories the application must write to at runtime.
WRITABLE_DIRS=(
    runtime
    runtime/logs
    console/runtime
    frontend/runtime
    backend/runtime
    assets
    frontend/assets
    backend/assets
    backend/web/assets
    frontend/web/assets
    backend/web/uploads
    frontend/web/uploads
    common/dokumen
    common/uploads
    common/uploads/rancangan
    common/uploads/masyarakat
    feed
    backups
)

# Config and dependency trees: readable by PHP, not world-accessible.
SENSITIVE_DIRS=(
    common/config
    backend/config
    frontend/config
    console/config
    vendor
)

# CLI entry points.
EXECUTABLE_FILES=(
    yii
    yii_test
    init
    update.sh
    install.sh
    scripts/set-safe-permissions.sh
)

while [ $# -gt 0 ]; do
    case "$1" in
        --app-dir)
            APP_DIR="$2"
            shift 2
            ;;
        --owner)
            OWNER="$2"
            shift 2
            ;;
        --web-user)
            WEB_USER="$2"
            shift 2
            ;;
        --web-group)
            WEB_GROUP="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            err "Unknown option: $1"
            usage
            exit 1
            ;;
    esac
done

APP_DIR="$(cd "${APP_DIR}" && pwd)"
validate_app_dir
resolve_owner
require_root
ensure_user_exists

log "Application: ${APP_DIR}"
log "Owner:       ${OWNER}:${WEB_GROUP}"
log "Web user:    ${WEB_USER}:${WEB_GROUP}"
[ "${DRY_RUN}" -eq 1 ] && warn "Dry run — no changes will be made."

log "Creating writable directories (if missing)..."
for rel in "${WRITABLE_DIRS[@]}"; do
    run mkdir -p "${APP_DIR}/${rel}"
done

log "Setting ownership to ${OWNER}:${WEB_GROUP}..."
run chown -R "${OWNER}:${WEB_GROUP}" "${APP_DIR}"

log "Base permissions: directories 755, files 644..."
run find "${APP_DIR}" -type d -exec chmod 755 {} +
run find "${APP_DIR}" -type f -exec chmod 644 {} +

log "Writable directories: 2775 (setgid, group writable)..."
for rel in "${WRITABLE_DIRS[@]}"; do
    if [ -d "${APP_DIR}/${rel}" ]; then
        run chmod 2775 "${APP_DIR}/${rel}"
    fi
done

log "Sensitive directories: 750..."
for rel in "${SENSITIVE_DIRS[@]}"; do
    if [ -d "${APP_DIR}/${rel}" ]; then
        run chmod 750 "${APP_DIR}/${rel}"
        run find "${APP_DIR}/${rel}" -type d -exec chmod 750 {} +
        run find "${APP_DIR}/${rel}" -type f -exec chmod 640 {} +
    fi
done

log "Secret and local config files: 640..."
while IFS= read -r -d '' file; do
    run chmod 640 "${file}"
done < <(find "${APP_DIR}" -type f \( \
    -name '.env' -o \
    -name '.env.*' -o \
    -name '*-local.php' \
    \) -print0 2>/dev/null)

log "CLI scripts: 750..."
for rel in "${EXECUTABLE_FILES[@]}"; do
    if [ -f "${APP_DIR}/${rel}" ]; then
        run chmod 750 "${APP_DIR}/${rel}"
    fi
done

if [ -d "${APP_DIR}/scripts" ]; then
    run find "${APP_DIR}/scripts" -type f -name '*.sh' -exec chmod 750 {} +
fi

log "Public document files: 644, no execute bit..."
if [ -d "${APP_DIR}/common/dokumen" ]; then
    run find "${APP_DIR}/common/dokumen" -type f -exec chmod 644 {} +
fi
if [ -d "${APP_DIR}/common/uploads" ]; then
    run find "${APP_DIR}/common/uploads" -type f -exec chmod 640 {} +
fi

log "Removing world-writable bits anywhere under app root..."
run find "${APP_DIR}" -xdev -type d -perm -0002 -exec chmod o-w {} +
run find "${APP_DIR}" -xdev -type f -perm -0002 -exec chmod o-w {} +

if [ -d "${APP_DIR}/.git" ]; then
    warn ".git directory found — locking down to owner only."
    run chmod -R 700 "${APP_DIR}/.git"
fi

for blocked in .env.example .gitignore composer.json composer.lock; do
    if [ -f "${APP_DIR}/${blocked}" ]; then
        run chmod 640 "${APP_DIR}/${blocked}"
    fi
done

log "Ensuring web user can traverse the app root..."
run chmod 755 "${APP_DIR}"

log "Done."
cat <<EOF

Summary:
  Owner:group     ${OWNER}:${WEB_GROUP}
  Writable dirs   2775 (runtime, assets, common/dokumen, feed, backups, uploads)
  Sensitive dirs  750/640 (config, vendor)
  Secrets         640 (.env, *-local.php)
  CLI scripts     750 (yii, init, *.sh)

If PHP-FPM runs as ${WEB_USER}, add the deploy user to that group when needed:
  sudo usermod -aG ${WEB_GROUP} ${OWNER}

Re-run after deployments that add new files or change ownership.

EOF
