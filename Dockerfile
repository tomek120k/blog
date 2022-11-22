FROM php:8.1-fpm AS app_php

WORKDIR /var/www/html

COPY docker/php/docker-wait-for-it.sh /usr/bin/docker-wait-for-it.sh

RUN chmod +x /var/www/html
RUN chmod +x /usr/bin/docker-wait-for-it.sh

RUN apt-get update && \
    apt-get install -y --no-install-recommends libssl-dev zlib1g-dev curl wget git unzip netcat libxml2-dev libpq-dev libzip-dev && \
    pecl install apcu && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install -j$(nproc) zip opcache intl pdo_pgsql pgsql && \
    docker-php-ext-enable apcu pdo_pgsql sodium && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN apt-get update \
        && apt-get install -y \
            librabbitmq-dev \
            libssh-dev \
        && docker-php-ext-install \
            bcmath \
            sockets \
        && pecl install amqp \
        && docker-php-ext-enable amqp

RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN pecl install -o -f redis && pecl clear-cache && docker-php-ext-enable redis

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
RUN symfony server:ca:install

COPY --from=composer /usr/bin/composer /usr/bin/composer

CMD composer i -o ; docker-wait-for-it database:5432 -- bin/console doctrine:migrations:migrate ;  php-fpm

HEALTHCHECK --timeout=3s --interval=5s \
  CMD curl -f http://localhost/ping || exit 1

EXPOSE 9000
