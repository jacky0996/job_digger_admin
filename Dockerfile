# PHP 8.2 FPM base image
FROM php:8.2-fpm

ENV TZ=Asia/Taipei
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /var/www

# 系統依賴 + PHP 擴充(Laravel 11 + PostgreSQL 用)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        zip \
        unzip \
        libzip-dev \
        libonig-dev \
        libpq-dev \
        netcat-openbsd \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql mbstring zip pcntl

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製專案
COPY . /var/www

# 預設權限
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Supervisor:同 container 內同時跑 php-fpm 與 schedule:work
# 直接覆蓋 main supervisord.conf,讓我們的檔案是唯一的真相來源
COPY docker-compose/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# Entrypoint:wait DB → composer install → migrate → exec supervisord
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
