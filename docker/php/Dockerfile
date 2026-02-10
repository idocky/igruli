FROM php:8.3-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libonig-dev \
    libssl-dev \
    libgmp-dev \
    libcurl4-openssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Установка и настройка PHP-расширений
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        gd \
        zip \
        gmp \
        exif \
        bcmath \
        mbstring \
        pcntl

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочего каталога
WORKDIR /var/www/html

# Установка прав для корректной работы Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

ARG USERNAME=wandry
ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} ${USERNAME} \
    && useradd -l -u ${UID} -g ${USERNAME} -m ${USERNAME} \
    && chown -R ${USERNAME}:${USERNAME} /var/www/html

USER ${USERNAME}

# Запуск PHP-FPM
CMD ["php-fpm"]

# Указываем порт (опционально, если используешь напрямую)
EXPOSE 9000
