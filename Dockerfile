FROM php:8.2-cli
RUN apt-get update -y \
#    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
#        autoconf \
#        curl \
#        default-mysql-client \
#        g++ \
#        git \
#        libcurl4-openssl-dev \
#        libfreetype6-dev \
#        libgd-dev \
#        libicu-dev \
#        libjpeg-dev \
#        libjpeg62-turbo-dev \
#        libltdl-dev \
#        libmagickwand-dev \
#        libmcrypt-dev \
#        libmemcached-dev \
#        libpcre3-dev \
#        libpng16-16 \
#        libxml2-dev \
#        libxslt-dev \
#        libxslt1-dev \
        libzip-dev
#        make \
#        vim \
#        zlib1g-dev
RUN docker-php-ext-install \
    bcmath \
#    bz2 \
#    calendar \
#    ctype \
#    curl \
#    dom \
#    exif \
#    fileinfo \
#    ftp \
#    gd \
#    gettext \
#    intl \
#    mysqli \
#    opcache \
#    pdo_mysql \
#    soap \
#    sockets \
#    tokenizer \
#    xsl \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html