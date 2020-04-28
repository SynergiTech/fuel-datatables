ARG PHP_VERSION=7.4
FROM php:$PHP_VERSION-cli-alpine

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer global require hirak/prestissimo

WORKDIR /package

COPY composer.json ./

RUN composer install

COPY . .

RUN vendor/bin/phpunit -c phpunit.xml.dist
