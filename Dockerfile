FROM php:8.3-apache

RUN apt-get update && apt-get install -y git unzip libicu-dev libzip-dev \
 && docker-php-ext-install pdo_mysql intl zip opcache \
 && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory /var/www/html/public>\n  FallbackResource /index.php\n</Directory>\n' > /etc/apache2/conf-available/symfony.conf \
 && a2enconf symfony
 
WORKDIR /var/www/html