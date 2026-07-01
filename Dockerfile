FROM docker.io/serversideup/php:8.5-cli-alpine AS backend

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist --ignore-platform-req=ext-bcmath

FROM docker.io/serversideup/php:8.5-frankenphp-alpine

WORKDIR /var/www/html

COPY --from=backend /var/www/html/vendor ./vendor

COPY . .

RUN install-php-extensions bcmath

ENV SERVER_NAME=:80

RUN mkdir -p ./resources/views

ENV AUTORUN_ENABLED=true

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0"]

EXPOSE 80
