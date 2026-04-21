FROM node:20-alpine AS frontend-build
WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS composer-build
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

FROM php:8.2-cli-alpine
WORKDIR /var/www/html

RUN apk add --no-cache bash icu-dev oniguruma-dev libzip-dev sqlite-dev \
    && docker-php-ext-install bcmath intl mbstring pdo pdo_mysql pdo_sqlite pcntl zip

COPY . .
COPY --from=composer-build /var/www/html/vendor ./vendor
COPY --from=frontend-build /var/www/html/public/build ./public/build
COPY docker/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh

RUN chmod +x /usr/local/bin/app-entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["app-entrypoint.sh"]
