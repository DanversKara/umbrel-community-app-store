FROM php:8.2-apache

# Install unzip and mysql client (needed for DB restore)
RUN apt-get update && apt-get install -y unzip default-mysql-client && rm -rf /var/lib/apt/lists/*

# Copy setup tool
COPY index.php /var/www/html/index.php

# Allow large file uploads (up to 500MB for main.zip and large SQL backups)
RUN echo "upload_max_filesize = 500M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 500M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80
