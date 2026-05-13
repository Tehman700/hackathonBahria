FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libxslt-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libsodium-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions required by Moodle
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        soap \
        xmlrpc \
        xsl \
        mbstring \
        exif \
        curl \
        opcache \
        sodium

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache document root to moodle/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# PHP config for Moodle
RUN echo "upload_max_filesize = 256M" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "post_max_size = 256M" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "max_input_vars = 5000" >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/moodle.ini

EXPOSE 80
