FROM php:8.2-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

COPY . .
COPY docker/entrypoint.sh /usr/local/bin/restaurant-entrypoint

RUN chmod +x /usr/local/bin/restaurant-entrypoint \
    && composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage bootstrap/cache public/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads

EXPOSE 8000

ENTRYPOINT ["restaurant-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
