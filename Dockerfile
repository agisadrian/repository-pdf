FROM php:8.2-apache

# Install kebutuhan Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && a2enmod rewrite \
    && a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Folder aplikasi
WORKDIR /var/www/html

# Copy project
COPY . .

# Install dependency Laravel
RUN composer install --no-dev --optimize-autoloader

# Ubah document root Apache ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Permission Laravel
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Port yang digunakan Apache (Railway isi otomatis lewat $PORT)
EXPOSE 80

# Saat container start: pasang port dari Railway, cache config, migrate, lalu jalankan Apache
CMD ["sh", "-c", "sed -ri \"s/Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -ri \"s/:80/:${PORT:-80}/\" /etc/apache2/sites-available/*.conf && php artisan config:clear && php artisan migrate --force && apache2-foreground"]