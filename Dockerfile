FROM docker.io/serversideup/php:8.5-cli-alpine AS backend

WORKDIR /var/www/html

COPY composer.json composer.lock /var/www/html/

RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist --ignore-platform-req=ext-bcmath

FROM docker.io/serversideup/php:8.5-frankenphp-alpine

WORKDIR /var/www/html

COPY --from=backend --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor

COPY --chown=www-data:www-data . /var/www/html

USER root

RUN install-php-extensions bcmath

USER www-data

ENV SERVER_NAME=:80

RUN mkdir -p /var/www/html/resources/views

ENV AUTORUN_ENABLED=true

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0"]

EXPOSE 80
