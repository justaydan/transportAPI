# Use an official PHP 8.2 runtime as a parent image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libssl-dev \
    pkg-config && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install MongoDB extension with SSL support
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache document root and permissions
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/public>|' /etc/apache2/apache2.conf

RUN echo '<Directory "/var/www/html/public">' >> /etc/apache2/apache2.conf
RUN echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf
RUN echo '    AllowOverride All' >> /etc/apache2/apache2.conf
RUN echo '    Require all granted' >> /etc/apache2/apache2.conf
RUN echo '</Directory>' >> /etc/apache2/apache2.conf

# Copy existing application directory with correct ownership
COPY --chown=www-data:www-data . /var/www/html

# Set permissions for directories (if necessary)
RUN chmod -R 755 /var/www/html/storage
RUN chmod -R 755 /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Apache service
CMD ["apache2-foreground"]
