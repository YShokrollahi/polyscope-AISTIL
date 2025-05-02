FROM php:8.0-apache
# Install dependencies including libvips with optimizations
RUN apt-get update && apt-get install -y \
    libvips-dev \
    libvips-tools \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    unzip \
    curl \
    && docker-php-ext-install -j$(nproc) gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Set up working directory
WORKDIR /var/www/html

# Copy just the essential files first to improve build caching
COPY scripts/ /var/www/html/scripts/
COPY src/ /var/www/html/src/
COPY templates/ /var/www/html/templates/
COPY config/ /var/www/html/config/

# Ensure OpenSeadragon files exist in templates directory
RUN mkdir -p /var/www/html/templates/js/images
RUN if [ ! -f /var/www/html/templates/js/openseadragon.min.js ]; then \
    echo "Downloading OpenSeadragon..." && \
    curl -L -o /var/www/html/templates/js/openseadragon.min.js https://cdn.jsdelivr.net/npm/openseadragon@3.0.0/build/openseadragon/openseadragon.min.js; \
    fi

# Download basic navigation images if they don't exist
RUN for img in home.png fullpage.png zoomin.png zoomout.png; do \
    if [ ! -f /var/www/html/templates/js/images/$img ]; then \
        curl -L -o /var/www/html/templates/js/images/$img https://raw.githubusercontent.com/openseadragon/openseadragon/master/images/$img; \
    fi; \
    done

# Make scripts executable
RUN chmod +x /var/www/html/scripts/*.sh

# Create output directory with proper permissions
RUN mkdir -p /var/www/html/www/output && \
    chown -R www-data:www-data /var/www/html

# Copy the entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

# Set environment variables for PHP optimization
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300

# Add a script to configure PHP at runtime
COPY <<'EOT' /usr/local/bin/docker-php-entrypoint-custom
#!/bin/sh
set -e

# Configure PHP with our optimized settings
echo "memory_limit = ${PHP_MEMORY_LIMIT}" > /usr/local/etc/php/conf.d/memory-limit.ini
echo "max_execution_time = ${PHP_MAX_EXECUTION_TIME}" > /usr/local/etc/php/conf.d/max-execution-time.ini

# Execute the original entrypoint
exec docker-entrypoint.sh "$@"
EOT

RUN chmod +x /usr/local/bin/docker-php-entrypoint-custom

ENTRYPOINT ["docker-php-entrypoint-custom"]
CMD ["apache2-foreground"]