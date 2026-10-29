#!/bin/sh
set -eu

port="${PORT:-10000}"

case "$port" in
    ''|*[!0-9]*)
        printf '%s\n' 'PORT must be a numeric TCP port.' >&2
        exit 1
        ;;
esac

sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf

cat > /etc/apache2/sites-available/000-default.conf <<APACHE
<VirtualHost *:${port}>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Options FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 combined
</VirtualHost>
APACHE

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache
chown www-data:www-data storage/app/private
chmod -R ug+rwX storage/framework storage/logs bootstrap/cache
chmod ug+rwx storage/app/private

php artisan config:cache

exec apache2-foreground
