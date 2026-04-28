FROM dunglas/frankenphp:php8.2.30-bookworm

RUN install-php-extensions \
    pdo_mysql \
    mysqli

COPY . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader --no-interaction || true

RUN chown -R 1000:1000 /app/storage /app/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000