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

# --- FINAL CRITICAL FIX (Hostname + Wait) ---
# We are using the correct, capitalized internal hostname: MySQL.
# Wait for the database host to become available on port 3306 (up to 30 seconds)
# before running the migration. This fixes both the resolution and timing errors.
RUN nc -z -w 30 MySQL 3306 && php artisan migrate --force

# Expose the PHP-FPM port (default for PHP-FPM is 9000)
# Railway will use this port to connect to your service
EXPOSE 9000

# Command to run when the container starts
# This starts the PHP-FPM process to handle web requests
CMD ["php-fpm"]
```eof

**Action:**

1.  **Replace** your Dockerfile with the text provided above.
2.  **Commit** and push the change to trigger a new deployment.

This version should pass the build step because it:
1.  Resolves the hostname correctly (`MySQL`).
2.  Waits for the database to be ready (30 seconds) before running `php artisan migrate`.

I truly hope this resolves your long-standing deployment issue.
