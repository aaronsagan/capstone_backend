# Stage 1: Build Stage (Includes all necessary tools for installation)
# We use a stable, recommended PHP base image. Change '8.2' if your app uses a different version.
FROM php:8.2-fpm-alpine AS base

# Install necessary dependencies, including the MySQL client, git, unzip, and netcat.
# Netcat (netcat-openbsd) is used to reliably wait for the database service to be ready.
RUN apk add --no-cache \
    mysql-client \
    git \
    unzip \
    netcat-openbsd \
    # Install the critical pdo_mysql extension
    && docker-php-ext-install pdo_mysql

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /app

# Copy all application files (including composer.json/lock)
COPY . /app

# Install PHP dependencies using Composer
# We skip dev dependencies and optimize the autoloader for production speed
RUN composer install --no-dev --optimize-autoloader

# We use the capitalized internal hostname: MySQL.
# Wait for the database host to become available on port 3306 (up to 30 seconds)
# before running the migration. This fixes both the resolution and timing errors.
RUN nc -z -w 30 MySQL 3306 && php artisan migrate --force

# Expose the PHP-FPM port (default for PHP-FPM is 9000)
# Railway will use this port to connect to your service
EXPOSE 9000

# Command to run when the container starts
# This starts the PHP-FPM process to handle web requests
CMD ["php-fpm"]
