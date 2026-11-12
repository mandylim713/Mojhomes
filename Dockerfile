# Mojhometouch — production image for Render (or any Docker host)
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev ca-certificates sqlite3 \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql zip exif \
 && a2enmod rewrite headers expires deflate remoteip \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-mojhometouch.ini
COPY docker/apache.conf /etc/apache2/conf-available/mojhometouch.conf
RUN a2enconf mojhometouch

WORKDIR /var/www/html
COPY . /var/www/html
RUN rm -f config.local.php \
 && mkdir -p storage/logs storage/proofs uploads/products uploads/gallery uploads/categories backups \
 && chown -R www-data:www-data /var/www/html \
 && chmod +x docker/entrypoint.sh

ENV PORT=10000 \
    DATA_DIR=/var/data \
    TRUST_PROXY=1
EXPOSE 10000
STOPSIGNAL SIGTERM
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
