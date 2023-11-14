FROM public.ecr.aws/e2o1q4v9/composer as build
COPY . /app/
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

FROM public.ecr.aws/e2o1q4v9/php-8.1-apache-buster as production

ENV APP_ENV=production
ENV APP_DEBUG=false

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    ffmpeg

RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

RUN docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install pdo pdo_mysql
COPY docker/php/conf.d/* /usr/local/etc/php/conf.d/

COPY --from=build /app /var/www/html
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN php artisan optimize && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan view:clear && \
    chmod 777 -R /var/www/html/storage/ && \
    chown -R www-data:www-data /var/www/ && \
    a2enmod rewrite &&\
    chmod 444 ./storage/oauth-* && \
    chmod 755 -R /var/www/html/public && \
    php artisan storage:link


CMD php artisan migrate --force && apache2-foreground
