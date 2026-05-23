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

# --- System deps + PHP extensions ---------------------------------------------
# Code-independent: this layer is cached across app changes and only rebuilds
# when the package/extension list below changes. Node is intentionally NOT
# installed here — Debian's `npm` package pulls ~586 transitive node-* packages
# (~158 MB), which is what was timing out cold builds. Node comes from the
# official image instead (see next stage).
RUN apt-get update && apt-get install -y \
    git \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    postgresql-client \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 \
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

# --- Node 20 from the official image ------------------------------------------
# Avoids Debian's ~586-package `npm`. The node binary is glibc-forward-compatible
# (a bookworm build runs fine on a newer base), and npm/npx are pure JS that run
# on it. Reuses the image already pulled by the `frontend` stage — no extra pull.
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# --- Composer binary ----------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# --- Playwright + Chromium for the audit:run crawler --------------------------
# The slowest build step. Installed BEFORE app code so an app change never
# re-downloads Chromium (this was the layer-ordering bug: it previously sat
# after `COPY . .`). Stored in /var/www/html/node_modules so require('playwright')
# resolves from any subdirectory (resources/audit-scripts/crawl.js, the inline
# `node -e "require('playwright')"` check, etc.).
RUN npm install --no-save playwright \
    && npx playwright install --with-deps chromium

# --- Startup scripts ----------------------------------------------------------
# Copied before the app source so they cache independently of code changes.
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/bootstrap.sh /usr/local/bin/docker-bootstrap
COPY docker/start-container.sh /usr/local/bin/start-container
COPY docker/start-queue.sh /usr/local/bin/start-queue
COPY docker/start-scheduler.sh /usr/local/bin/start-scheduler
RUN chmod +x /usr/local/bin/docker-bootstrap /usr/local/bin/start-container \
             /usr/local/bin/start-queue /usr/local/bin/start-scheduler

# --- Application code + build artifacts ----------------------------------------
# Changes most often, so it comes last. .dockerignore excludes node_modules,
# vendor, and public/build, so this copy does not clobber the Playwright install
# above or the artifacts brought in from the build stages.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start-container"]
