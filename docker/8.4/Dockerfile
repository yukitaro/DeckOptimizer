# syntax=docker/dockerfile:1.7
# -----------------------------------------------------------
# Stage 1: Builder - php CLI (Debian) for deterministic composer + optional frontend build
# -----------------------------------------------------------
FROM php:8.4-cli-bullseye AS builder

ENV DEBIAN_FRONTEND=noninteractive
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    apt-get update \
    && apt-get install -y --no-install-recommends \
       ca-certificates curl git unzip zip build-essential \
       libzip-dev libpng-dev libjpeg-dev zlib1g-dev pkg-config \
       nodejs npm yarn \
    && rm -rf /var/lib/apt/lists/*

ARG COMPOSER_ALLOW_SUPERUSER=1
ARG NODE_VERSION=22

ENV TZ=America/Los_Angeles
WORKDIR /app

# Install composer explicitly (deterministic)
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
  && php /tmp/composer-setup.php --install-dir=/usr/bin --filename=composer \
  && chmod +x /usr/bin/composer \
  && rm /tmp/composer-setup.php

# Copy composer files (for caching) then copy full repo so path repos exist for composer
COPY composer.json composer.lock ./
COPY . .


# Run composer with the full repo available (path repositories resolved)
RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts \
  && composer dump-autoload --optimize --no-dev


# Optional frontend build (uncomment if you build assets here)
# RUN cd frontend && yarn install --frozen-lockfile && yarn build

# -----------------------------------------------------------
# Stage 2: Release - slim runtime (Alpine) with compiled extensions
# -----------------------------------------------------------
FROM php:8.4-fpm-alpine AS release

ARG WWWGROUP=1000
ENV TZ=America/Los_Angeles
WORKDIR /var/www/html

# Install build deps, runtime libs, compile needed extensions, then remove build deps
RUN apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      mariadb-connector-c-dev \
      libzip-dev \
      libpng-dev \
      libjpeg-turbo-dev \
      freetype-dev \
      bzip2-dev \
      linux-headers \
    && apk add --no-cache \
      mysql-client \
      postgresql-client \
      supervisor \
      libzip \
      libpng \
      libjpeg-turbo \
      freetype \
      bash \
      curl \
      bzip2 \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(getconf _NPROCESSORS_ONLN)" pdo pdo_mysql zip gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Copy app + vendor from builder
COPY --from=builder /app/vendor /var/www/html/vendor
COPY --from=builder /app /var/www/html

# Runtime configuration and lightweight entrypoint
COPY docker/8.4/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/8.4/php.ini /usr/local/etc/php/conf.d/99-sail.ini
COPY docker/8.4/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container || true
    
# Create non-root user (sail uid 1337) and set group
RUN addgroup -g $WWWGROUP sail \
 && adduser -u 1337 -G sail -D -H sail

# Supervisor + Laravel runtime dirs (now that sail exists)
RUN mkdir -p /var/log/supervisor /tmp/supervisor \
 && mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
 && mkdir -p /var/www/html/storage/logs \
 && mkdir -p /var/www/html/bootstrap/cache \
 && chown -R sail:sail /var/www/html /var/log/supervisor /tmp/supervisor \
 && chmod -R u+w /var/www/html /var/log/supervisor /tmp/supervisor

USER sail
EXPOSE 9000
CMD ["/bin/sh", "-c", "mkdir -p /var/log/supervisor /tmp/supervisor && exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf"]
