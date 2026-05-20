FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        intl \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-scripts

RUN chown -R www-data:www-data var/

RUN chmod 644 config/jwt/private.pem
