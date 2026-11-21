FROM mcr.microsoft.com/devcontainers/php:4-8.4

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client \
    && apt-get install -y --no-install-recommends libicu-dev libpq-dev \
    && docker-php-ext-install -j"$(nproc)" exif intl pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER vscode
