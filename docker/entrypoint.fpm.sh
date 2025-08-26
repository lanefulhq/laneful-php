#!/bin/bash
set -e

# Ensure vendor directory exists and has dependencies
if [ ! -d "/var/www/html/vendor" ] || [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    cd /var/www/html
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Start PHP-FPM
exec php-fpm
