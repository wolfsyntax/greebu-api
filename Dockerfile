FROM public.ecr.aws/e2o1q4v9/composer as build
COPY . /app/
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

FROM public.ecr.aws/e2o1q4v9/php-8.1-apache-buster as production

ENV APP_ENV=production
ENV APP_DEBUG=false

RUN docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install pdo pdo_mysql
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=build /app /var/www/html
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN composer dump-autoload && \ 
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan view:clear && \
    chmod 777 -R /var/www/html/storage/ && \
    chown -R www-data:www-data /var/www/ && \
    a2enmod rewrite &&\
    chmod 444 ./storage/oauth-* &&\
    php artisan optimize

CMD php artisan migrate --force && apache2-foreground
