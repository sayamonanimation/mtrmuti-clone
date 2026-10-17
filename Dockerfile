FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/assets/uploads /var/www/html/storage \
    && chmod -R 775 /var/www/html/assets/uploads /var/www/html/storage

EXPOSE 80
