FROM php:8.2-apache

# Enable fileinfo extension used for mime_content_type()
RUN docker-php-ext-install fileinfo

COPY . /var/www/html/

# hostPath dir will be mounted at /data at runtime
RUN mkdir -p /data/photos /data/metadata && chown -R www-data:www-data /data

EXPOSE 80
