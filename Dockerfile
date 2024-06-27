FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip

RUN curl https://getcomposer.org/installer > /tmp/composer_install
RUN php /tmp/composer_install --install-dir=/bin --filename=composer

RUN pecl install xdebug-3.3.2 && docker-php-ext-enable xdebug

WORKDIR /code

CMD ["php-fpm"]
