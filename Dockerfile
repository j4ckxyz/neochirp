FROM php:8.4-apache

# Install SQLite dev libs (required to compile pdo_sqlite)
# pdo is already built into PHP 8.4, so only install pdo_sqlite
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite

# Enable Apache mod_rewrite (needed for clean URLs)
RUN a2enmod rewrite

# Set the web root
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copy project files into the image
COPY . /var/www/html/

# Apache config: set document root + allow .htaccess overrides
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf && \
    sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Write .htaccess for clean URLs
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteRule ^(.*)$ $1/index.php [L]\n\
</IfModule>' > /var/www/html/.htaccess

# Suppress PHP errors in the browser — log to stderr (Docker picks this up)
RUN echo 'display_errors = Off' > /usr/local/etc/php/conf.d/chirp.ini && \
    echo 'log_errors = On' >> /usr/local/etc/php/conf.d/chirp.ini && \
    echo 'error_log = /dev/stderr' >> /usr/local/etc/php/conf.d/chirp.ini && \
    echo 'expose_php = Off' >> /usr/local/etc/php/conf.d/chirp.ini && \
    echo 'session.cookie_httponly = 1' >> /usr/local/etc/php/conf.d/chirp.ini && \
    echo 'session.use_strict_mode = 1' >> /usr/local/etc/php/conf.d/chirp.ini

# The DB lives one level above the web root (/var/www/chirp.db).
# It is mounted as a volume — not baked into the image.
RUN mkdir -p /var/www && \
    chown -R www-data:www-data /var/www

# Startup: initialise DB from sample if not present, then start Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
