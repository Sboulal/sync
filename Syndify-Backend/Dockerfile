# ==========================================
# Stage 1: Build Stage (Composer)
# ==========================================
FROM php:8.4-fpm-alpine AS vendor-builder

# Installer zip w curl bach ykhdem composer mzyan
RUN apk add --no-cache curl zip unzip

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /app

# Copier les fichiers composer w n-installiw sans les dev-dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# ==========================================
# Stage 2: Final Production Stage
# ==========================================
FROM php:8.4-fpm-alpine

# Installer les dépendances système li bghiti l PostgreSQL w GD (b Alpine)
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    postgresql-dev

# Installer les extensions PHP dyalek
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pgsql zip gd

# 🟢 Hna ghadi nweliw bhal Yassine: /var/www blast /var/www/html
WORKDIR /var/www

# Njibou le dossier vendor men l'étape 1 (li fih composer wajed)
COPY --from=vendor-builder /app/vendor ./vendor

# Copier l'application dyalek kamla
COPY . .

# N9addo le cache w les permissions kima bgha Yassine l'Laravel
RUN rm -rf bootstrap/cache && \
    mkdir -p bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Set production environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false

# Optimisation PHP-FPM pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/memory.ini"

EXPOSE 9000