FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash git curl zip unzip \
    libpq-dev postgresql-dev \
    mysql-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && docker-php-ext-enable pdo_mysql pdo_pgsql \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
