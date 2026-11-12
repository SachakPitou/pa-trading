# Local dev image. Same PHP major version as the VPS (check with `php -v` there; change PHP_VERSION if it differs).
ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli
RUN docker-php-ext-install pdo_mysql
WORKDIR /app
