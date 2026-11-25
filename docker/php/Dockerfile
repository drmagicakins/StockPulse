# php-fpm 8.4 development image for the Laravel API (WP 1B).
# Production hardening (no dev tools, read-only FS, digest pinning) is handled in WP 1I / Phase 10.
FROM php:8.4-fpm

ARG HOST_UID=1000
ARG HOST_GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip curl libpq-dev libicu-dev libzip-dev \
      libpng-dev libjpeg-dev libfreetype-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl bcmath gd zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-stockpulse.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-stockpulse.conf

RUN groupadd -g "${HOST_GID}" app \
    && useradd -u "${HOST_UID}" -g "${HOST_GID}" -m -s /bin/bash app \
    && mkdir -p /var/www/html \
    && chown app:app /var/www/html

WORKDIR /var/www/html
USER app
EXPOSE 9000
CMD ["php-fpm"]
