FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev libssl-dev libxml2-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    zlib1g-dev libmagickwand-dev --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql curl mbstring dom xml zip gd iconv bcmath

# Imagick via PECL
RUN pecl install imagick && docker-php-ext-enable imagick

# Apache mod_rewrite
RUN a2enmod rewrite

# Point DocumentRoot at /public subfolder
RUN sed -i \
    's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides (needed for URL shortener routing)
RUN printf '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' >> /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
