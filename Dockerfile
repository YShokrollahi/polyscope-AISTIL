FROM php:8.0-apache

# Install dependencies including libvips
RUN apt-get update && apt-get install -y \
    libvips-dev \
    libvips-tools \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install gd zip

# Enable Apache modules
RUN a2enmod rewrite

# Set up working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Create output directory with proper permissions
RUN mkdir -p /var/www/html/www/output && \
    chown -R www-data:www-data /var/www/html

# Create the entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]