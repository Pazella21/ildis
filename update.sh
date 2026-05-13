#!/usr/bin/env bash
#
# ILDIS Update Script (wrapper)
# This script has been replaced by install.sh --update.
# Kept for backward compatibility.
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