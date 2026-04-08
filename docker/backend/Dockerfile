FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    icu-dev \
    libpq-dev \
    libzip-dev \
    git \
    linux-headers \
    rabbitmq-c-dev \
    fcgi

RUN docker-php-ext-install \
    pdo_pgsql \
    intl \
    opcache \
    zip

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install amqp && \
    docker-php-ext-enable amqp && \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck && \
    echo 'SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1' >> /usr/local/bin/php-fpm-healthcheck && \
    chmod +x /usr/local/bin/php-fpm-healthcheck

RUN echo 'memory_limit=-1' > /usr/local/etc/php/conf.d/memory.ini

WORKDIR /app

FROM base AS dev

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del .build-deps

RUN addgroup -g 1000 app && \
    adduser -u 1000 -G app -s /bin/sh -D app && \
    chown -R app:app /app

USER app

FROM base AS prod

COPY backend/composer.json backend/composer.lock* ./

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY backend/ .

RUN composer dump-autoload --no-dev --classmap-authoritative

RUN addgroup -g 1000 app && \
    adduser -u 1000 -G app -s /bin/sh -D app && \
    chown -R app:app /app

USER app
