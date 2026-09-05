FROM php:8.2-apache

# Install PDO MySQL and required extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    ca-certificates \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy project files into Apache web root
COPY . /var/www/html/

# Configure Apache to allow .htaccess and rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

ENV PORT=80
EXPOSE 80

# Dynamically bind Apache to $PORT passed by Render (default 10000 or 80) and run Apache
CMD sh -c "sed -i 's/Listen [0-9]*/Listen '\$PORT/ /etc/apache2/ports.conf && sed -i 's/<VirtualHost \*:[0-9]*>/<VirtualHost \*:'\$PORT'>/' /etc/apache2/sites-available/000-default.conf && apache2-foreground"
