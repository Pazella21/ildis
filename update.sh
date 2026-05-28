#!/usr/bin/env bash
#
# Skrip Pembaruan ILDIS (wrapper)
# Skrip ini telah digantikan oleh install.sh --update.
# Dipertahankan untuk kompatibilitas mundur.
#

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ ! -f "${SCRIPT_DIR}/install.sh" ]; then
    echo "install.sh tidak ditemukan di ${SCRIPT_DIR}, mengunduh dari GitHub..."
    curl -fsSL "https://raw.githubusercontent.com/bphndigitalservice/ildis/main/install.sh" -o "${SCRIPT_DIR}/install.sh"
    if [ -f "${SCRIPT_DIR}/install.sh" ]; then
        chmod +x "${SCRIPT_DIR}/install.sh"
        echo "install.sh berhasil diunduh."
    else
        echo "KESALAHAN: Gagal mengunduh install.sh."
        echo "Unduh manual dari: https://github.com/bphndigitalservice/ildis"
        exit 1
    fi
fi

exec "${SCRIPT_DIR}/install.sh" --update "$@"