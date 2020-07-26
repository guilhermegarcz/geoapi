FROM php:7.4-fpm
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        zip \
        libzip4 \
        libicu-dev \
        zlib1g-dev \
        libbz2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-configure mysqli \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure bz2 \
    && docker-php-ext-configure exif \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip exif mysqli intl bz2
COPY "php.ini" "$PHP_INI_DIR/php.ini"
