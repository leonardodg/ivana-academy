# =============================================================================
# Ivana Academy — Moodle 4.5
# Multi-stage build: composer → base → production | development
#
# Build production:
#   docker build --target production -f .devcontainer/build/moodle.Dockerfile .
#
# Build development:
#   docker build --target development -f .devcontainer/build/moodle.Dockerfile .
# =============================================================================

# =============================================================================
#            STAGE COMPOSER — resolve dependencies PHP isolated
# =============================================================================
FROM composer:lts AS composer-base

    RUN apk add --no-cache \
            libpng-dev libjpeg-turbo-dev freetype-dev \
            icu-dev \
            libzip-dev \
            zlib-dev

    RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd intl zip

    WORKDIR /app

    COPY composer.json composer.lock ./

# =============================================================================
#     STAGE COMPOSER-PROD — dependencies production sem dev packages
# =============================================================================
FROM composer-base AS composer-prod

    RUN --mount=type=cache,target=/tmp/composer-cache \
        composer install \
            --no-dev \
            --no-interaction \
            --no-progress \
            --prefer-dist \
            --no-scripts

# =============================================================================
#     STAGE COMPOSER-DEV — dependencies development COM dev packages
# =============================================================================
FROM composer-base AS composer-dev

    RUN --mount=type=cache,target=/tmp/composer-cache \
        composer install \
            --no-interaction \
            --no-progress \
            --prefer-dist \
            --no-scripts

# =============================================================================
#         STAGE BASE — SETUP SHARED BETWEEN DEVELOPMENT AND PRODUCTION
# =============================================================================
FROM moodlehq/moodle-php-apache:8.2 AS base

# Metadata
LABEL maintainer="LeoDG <callme@leodg.dev>" \
      org.opencontainers.image.title="Ivana Academy - Moodle 4.5" \
      org.opencontainers.image.source="https://github.com/leonardodg/ivana-academy" \
      org.opencontainers.image.version="4.5"

ENV MOODLE_DBTYPE=mysqli \
    MOODLE_DBLIB=native \
    MOODLE_DBPFX=mdl_ \
    MOODLE_DBCOLL=utf8mb4_bin \
    MOODLE_URL=https://develop.local \
    MOODLE_DATA=/var/www/moodledata \
    MOODLE_ADMIN=admin \
    MOODLE_DBHOST=db \
    MOODLE_DBNAME=moodle \
    ENVIRONMENT=development \
    TZ=America/Sao_Paulo

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       gettext-base  \
       cron \
       libpng-dev \
    && apt-get install -y --no-install-recommends --only-upgrade \
       openssl \
       libssl-dev \
       libssl3 \
       apache2 \
       apache2-bin \
       apache2-data \
       libapache2-mod-php8.2 \
       libpam-runtime \
       libpam0g \
       gnutls-bin \
       libgnutls30 \
       libxml2 \
       libtiff6 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN mkdir -p /etc/ssl/certs \
    && mkdir -p /docker-entrypoint.d/ \
    && chown -R www-data:www-data /etc/ssl/certs \
    && chmod 755 /etc/ssl/certs \
    && mkdir -p /var/www/moodledata && chown -R www-data:www-data /var/www/moodledata

COPY --from=composer-base /usr/bin/composer /usr/bin/composer
COPY .devcontainer/php/opcache.ini \
     .devcontainer/php/uploads.ini \
     /usr/local/etc/php/conf.d/

# Apache: módulos e configuração via template (URL vem em runtime)
COPY .devcontainer/apache/*.template /etc/apache2/sites-available/

# Add Script to CRONTAB
COPY --chown=www-data:www-data .devcontainer/bin/moodle-cron /var/www/html/moodle-cron

# Add Script Entrypoint
COPY --chown=www-data:www-data .devcontainer/bin/moodle-entrypoint /usr/local/bin/moodle-entrypoint

# Configure Moodle after installation
COPY --chown=www-data:www-data .devcontainer/config/config-docker.php /var/www/html/config.php

RUN chmod +x /var/www/html/moodle-cron \
    && chmod +x /usr/local/bin/moodle-entrypoint


EXPOSE 80 443
USER root
ENTRYPOINT ["moodle-entrypoint"]
CMD ["apache2-foreground"]


# =============================================================================
#               STAGE PRODUCTION — Optimized for performance
# =============================================================================
FROM base AS production

ENV ENVIRONMENT=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# REMOVE git/gnupg
RUN apt-get remove --purge -y \
       git git-man \
       gnupg gnupg2 gpg gpg-agent \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia vendor otimizado do stage composer
COPY --from=composer-prod --chown=www-data:www-data /app/vendor/ /var/www/html/vendor/

# Copia o código da aplicação Moodle
COPY --chown=www-data:www-data . .

# Otimiza autoloader para produção
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

# Limpeza final
RUN rm /usr/bin/composer

# =============================================================================
#          STAGE DEVELOPMENT — Tools (hot-reload, xdebug, etc)
# =============================================================================
FROM base AS development

# Desabilita opcache validate timestamps para dev (melhor hot-reload)
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    ENVIRONMENT=development

# Instala apenas o necessário, limpa cache no mesmo RUN
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       openssl \
       nano \
       curl \
       default-mysql-client \
       git \
       git-man \
       gnupg \
       gnupg2 \
       gpg \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/pear

COPY .devcontainer/php/opcache-dev.ini /usr/local/etc/php/conf.d/moodle-opcache.ini

# Vendor to development
COPY --from=composer-dev --chown=www-data:www-data /app/vendor/ /var/www/html/vendor/

# Copia todo o código (em dev geralmente será sobrescrito por volume)
COPY --chown=www-data:www-data . .

# Em desenvolvimento: autoloader normal (permite hot-reload)
RUN composer dump-autoload --no-interaction
