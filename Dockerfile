FROM php:8.5-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        git \
        libzip-dev \
        libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN cp .env.example .env \
    && php artisan key:generate --force

RUN mkdir -p /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE $PORT

CMD ["sh", "-c", "mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views && php artisan config:clear; echo '=== ENV CHECK ==='; echo \"DATABASE_URL=$DATABASE_URL\"; if [ -n \"$DATABASE_URL\" ]; then export DB_USERNAME=$(echo \"$DATABASE_URL\" | sed -n 's|^[a-z]*://\\([^:]*\\):.*|\\1|p'); export DB_PASSWORD=$(echo \"$DATABASE_URL\" | sed -n 's|^[a-z]*://[^:]*:\\([^@]*\\)@.*|\\1|p'); export DB_HOST=$(echo \"$DATABASE_URL\" | sed -n 's|^[a-z]*://[^@]*@\\([^:/]*\\).*|\\1|p'); export DB_PORT=$(echo \"$DATABASE_URL\" | sed -n 's|^[a-z]*://[^@]*@[^:/]*:\\([0-9]*\\)/.*|\\1|p'); export DB_DATABASE=$(echo \"$DATABASE_URL\" | sed -n 's|^[a-z]*://[^@]*@[^/]*/\\([^?]*\\).*|\\1|p'); export DB_PORT=${DB_PORT:-5432}; echo \"Resolved DB_HOST=$DB_HOST DB_PORT=$DB_PORT DB_DATABASE=$DB_DATABASE DB_USER=$DB_USERNAME\"; sed -i \"s|^DB_HOST=.*|DB_HOST=$DB_HOST|\" .env; sed -i \"s|^DB_PORT=.*|DB_PORT=$DB_PORT|\" .env; sed -i \"s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|\" .env; sed -i \"s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|\" .env; sed -i \"s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|\" .env; echo '=== .env DB section ==='; grep -E '^DB_' .env; fi; echo '=== LARAVEL PG CONFIG ==='; php artisan tinker --execute=\"dump(config('database.connections.pgsql'));\"; php artisan migrate --force --seed; echo '--- APP STARTED on port $PORT ---' && php artisan serve --host=0.0.0.0 --port=$PORT"]
