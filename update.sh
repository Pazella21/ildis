#!/usr/bin/env bash
#
# Skrip Pembaruan ILDIS (wrapper)
# Skrip ini telah digantikan oleh install.sh --update.
# Dipertahankan untuk kompatibilitas mundur.
#

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -f "${SCRIPT_DIR}/install.sh" ]; then
    exec "${SCRIPT_DIR}/install.sh" --update "$@"
else
    echo "KESALAHAN: install.sh tidak ditemukan di ${SCRIPT_DIR}"
    echo "Unduh rilis terbaru dari:"
    echo "  https://github.com/bphndigitalservice/ildis"
    exit 1
fi