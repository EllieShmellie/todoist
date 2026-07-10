FROM php:8.3-cli-alpine

RUN apk add --no-cache git oniguruma sqlite-libs unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS oniguruma-dev sqlite-dev \
    && docker-php-ext-install -j"$(nproc)" mbstring pdo_sqlite \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize --no-interaction

COPY docker/backend-entrypoint.sh /usr/local/bin/todo-backend
RUN chmod +x /usr/local/bin/todo-backend

EXPOSE 8000

ENTRYPOINT ["todo-backend"]
