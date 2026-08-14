# ---------------------------------------------------------------
# Etapa 1: compilar assets (Vite: Monaco, Bootstrap, Tailwind, Sass)
# ---------------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------------------------------------------------------------
# Etapa 2: dependencias PHP (sin dev, sin scripts todavía)
# Corre sobre PHP 8.2 (igual que la app): la imagen composer:2 trae
# PHP 8.5 y el lock (nette/schema) exige php 8.1-8.4.
# ---------------------------------------------------------------
FROM php:8.2-cli-alpine AS vendor
RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# ---------------------------------------------------------------
# Etapa 3: imagen de la aplicación (PHP-FPM)
# ---------------------------------------------------------------
FROM php:8.2-fpm-alpine AS app
WORKDIR /var/www

RUN apk add --no-cache su-exec \
    && docker-php-ext-install pcntl bcmath opcache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-usm-coder.ini

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------
# Etapa 4: Nginx con los archivos públicos (estáticos + assets)
# ---------------------------------------------------------------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/public /var/www/public
