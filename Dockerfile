# PHP 8.2 & Alpine Linux အခြေခံ Image
FROM php:8.2-fpm-alpine

# လိုအပ်သော System Dependencies များ တပ်ဆင်ခြင်း
RUN apk add --no-cache \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    supervisor \
    nginx

# PHP Extensions များ တပ်ဆင်ခြင်း
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Composer ရယူခြင်း
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Work Directory သတ်မှတ်ခြင်း
WORKDIR /var/www/html

# Source code များကို ကူးယူခြင်း
COPY . .

# Composer Dependencies များကို Install လုပ်ခြင်း
RUN composer install --no-dev --optimize-autoloader

# Storage & Cache permissions ပေးခြင်း
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Coolify / Reverb အတွက် Port ဖွင့်ပေးခြင်း
EXPOSE 8000

# Container စတင်မည့် Command (Migration & Server Run)
CMD php artisan migrate --force && php artisan optimize && php artisan reverb:start --host=0.0.0.0 --port=8000