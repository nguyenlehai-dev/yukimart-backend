#!/bin/bash
# PHP wrapper that loads fileinfo extension
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
/www/server/php/83/bin/php -d "extension=${SCRIPT_DIR}/php-ext/fileinfo.so" "$@"
