#!/bin/sh
# Garante que os diretórios de storage e cache do Laravel são graváveis pelo PHP-FPM (www-data).
# Necessário no Podman rootless onde os arquivos do host são mapeados como root no container.
chmod -R 777 /var/www/html/src/storage /var/www/html/src/bootstrap/cache 2>/dev/null || true

exec php-fpm "$@"
