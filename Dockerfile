FROM php:8.4-cli-alpine

COPY . /opt/source
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# install dependencies for php modules
RUN apk add icu-dev libzip-dev git && \
    docker-php-ext-install zip intl

# build project
RUN cd /opt/source && \
    rm -r -f composer.lock vendor && \
    composer update --no-dev

ENTRYPOINT ["/usr/local/bin/php", "/opt/source/bin/descarga-masiva.php"]
