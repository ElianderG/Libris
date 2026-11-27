FROM php:8.3-cli-bookworm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node:22.23.5-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22.23.5-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && corepack enable \
    && corepack prepare yarn@1.22.22 --activate

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R a+rwx storage bootstrap/cache \
    && export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')" \
    && composer dump-autoload --optimize --no-interaction \
    && cp .env.example .env \
    && php artisan wayfinder:generate --with-form \
    && yarn install --frozen-lockfile \
    && yarn run build \
    && rm -f .env

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
