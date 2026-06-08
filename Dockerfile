FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash git curl zip unzip su-exec \
    libpq-dev postgresql-dev \
    mysql-dev \
    freetype libjpeg-turbo libpng libwebp \
    linux-headers \
    && apk add --no-cache --virtual .build-deps \
    freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql sockets pcntl gd \
    && docker-php-ext-enable pdo_mysql pdo_pgsql sockets pcntl gd \
    && apk del .build-deps linux-headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN git config --global --add safe.directory /var/www/html \
    && APP_ENV=local SIGNATURE_DRIVER=null composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
