FROM composer:2 AS vendor

WORKDIR /app

RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.4-apache

WORKDIR /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    nodejs \
    npm \
    postgresql-client \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . .
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/bootstrap.sh /usr/local/bin/docker-bootstrap
COPY docker/start-container.sh /usr/local/bin/start-container
COPY docker/start-queue.sh /usr/local/bin/start-queue
COPY docker/start-scheduler.sh /usr/local/bin/start-scheduler

# Install Playwright npm package + Chromium browser for audit:run crawler.
# Stored in /var/www/html/node_modules so require('playwright') resolves from
# any subdirectory (crawl.js, checkPlaywright() inline eval, etc.).
RUN npm install --no-save playwright \
    && npx playwright install --with-deps chromium

RUN chmod +x /usr/local/bin/docker-bootstrap /usr/local/bin/start-container /usr/local/bin/start-queue /usr/local/bin/start-scheduler \
    && mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start-container"]
