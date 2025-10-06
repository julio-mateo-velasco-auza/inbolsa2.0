# ===========================================
# docker/php-apache/Dockerfile
# ===========================================
FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite

RUN { \
  echo 'upload_max_filesize=16M'; \
  echo 'post_max_size=16M'; \
  echo 'memory_limit=256M'; \
  echo 'max_execution_time=60'; \
} > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
