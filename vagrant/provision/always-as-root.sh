#!/usr/bin/env bash

#== Bash helpers ==

function info {
  echo " "
  echo "--> $1"
  echo " "
}

#== Provision script ==

info "Provision-script user: $(whoami)"

# Ganti ke versi PHP sesuai yang di-install dari Ondřej (lihat once-as-root.sh)
PHP_VERSION="8.3"

info "Restart web stack"
systemctl restart php${PHP_VERSION}-fpm
systemctl restart nginx
systemctl restart mysql
