FROM php:8.3.6-fpm

# Update and install necessary tools
RUN apt-get update && apt-get install -y bash git curl unzip libssl-dev zlib1g-dev libpng-dev libssh2-1-dev libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd zip opcache bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set PHP timezone
RUN echo 'date.timezone = Europe/Belgrade' >> /usr/local/etc/php/conf.d/docker-php-timezone.ini;

# Set PHP memory limit
RUN echo 'memory_limit = 2048M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
