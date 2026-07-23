FROM php:8.2-apache

COPY . /var/www/html/

RUN mkdir -p /data/photos /data/metadata && \
    chown -R www-data:www-data /data

EXPOSE 80
