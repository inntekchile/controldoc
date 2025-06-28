#!/usr/bin/env bash
set -e

# Iniciar supervisor, que a su vez iniciará Nginx y PHP-FPM
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
