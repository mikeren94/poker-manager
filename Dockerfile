FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip curl \
    && docker-php-ext-install pdo_mysql

COPY . /var/www/html
WORKDIR /var/www/html
RUN rm -rf /var/www/html/index.php
RUN ln -s public /var/www/html/html
# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set Apache DocumentRoot to Laravel's public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Grant access to public folder
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" >> /etc/apache2/apache2.conf

RUN a2enmod rewrite
RUN sed -i 's|AllowOverride None|AllowOverride All|' /etc/apache2/apache2.conf
EXPOSE 80