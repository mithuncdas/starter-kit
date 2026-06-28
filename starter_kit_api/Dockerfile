FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions in separate steps for better error handling
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Composer with increased timeout
RUN composer config --global process-timeout 600

# Accept build arguments for user and group
ARG USER_ID
ARG GROUP_ID

ARG USER_NAME
ARG GROUP_NAME

# Create a non-root user and group with the specified IDs
RUN groupadd -g ${GROUP_ID} ${GROUP_NAME} && \
    useradd -u ${USER_ID} -g ${GROUP_NAME} -m -d /home/${USER_NAME} -s /bin/bash ${USER_NAME}

# Set working directory
WORKDIR /var/www/html


# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev

# Copy existing application directory contents
COPY . ./


# Install all dependencies including dev dependencies (skip scripts to avoid artisan issues)
RUN composer install --optimize-autoloader --no-scripts

# Generate autoload files (skip scripts to avoid artisan issues)
RUN composer dump-autoload --optimize --no-scripts

# Run composer scripts now that everything is set up
RUN composer run-script post-autoload-dump || true


# Change ownership to the non-root user and group
RUN chown -R ${USER_NAME}:${GROUP_NAME} /var/www/html

# Set proper permissions for Laravel directories
RUN chmod -R 755 /var/www/html
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

# Create bootstrap/cache directory if it doesn't exist and set permissions
RUN mkdir -p /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/bootstrap/cache

# Ensure storage subdirectories exist with proper permissions
RUN mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && chmod -R 775 /var/www/html/storage \
    && chown -R ${USER_NAME}:${GROUP_NAME} /var/www/html/storage



# Increase PHP memory_limit to 512M (must be done as root)
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# PHP execution timeout
RUN echo "max_execution_time=600" > /usr/local/etc/php/conf.d/timeout.ini \
 && echo "default_socket_timeout=120" >> /usr/local/etc/php/conf.d/timeout.ini

# PHP-FPM request terminate timeout
RUN echo "request_terminate_timeout = 600" >> /usr/local/etc/php-fpm.d/www.conf

# PHP-FPM pool settings
RUN echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
 && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
 && echo "pm.min_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
 && echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/www.conf

# Increase PHP upload limits for large file uploads (API file/image uploads)
RUN echo "upload_max_filesize=100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini

# Switch to the non-root user
USER ${USER_NAME}

# Expose PHP-FPM port and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
