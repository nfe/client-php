# Dev/CI image for the NFE.io PHP SDK.
#
# The PHP version is set at build time (default 8.2 matches the project floor).
# We avoid alpine for broader extension compatibility — the size cost is paid
# once at pull time and the image is shared across dev and CI.
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

# Apt minimal: git for composer, unzip for archive extraction, the libs that
# back the PHP extensions we need (mbstring -> oniguruma, zip -> libzip).
# Curl, json, openssl are baked into the official PHP image already.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Pull a real composer binary from the official image. No script-installer
# chicanery, no version drift.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create a non-root user matching the host UID so files written through the
# bind mount stay owned by the developer, not root.
ARG UID=1000
ARG GID=1000
RUN groupadd --system --gid ${GID} app \
    && useradd --system --uid ${UID} --gid ${GID} --create-home --shell /bin/bash app

USER app
WORKDIR /app

# Composer cache survives container recreation via a named volume mounted here.
ENV COMPOSER_HOME=/home/app/.composer \
    COMPOSER_ALLOW_SUPERUSER=0 \
    PHP_CS_FIXER_IGNORE_ENV=1

CMD ["php", "-v"]
