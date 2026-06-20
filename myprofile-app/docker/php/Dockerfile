FROM php:8.3-fpm
WORKDIR /var/www
RUN rm -rf /var/www/html

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    apt-utils \
    libonig-dev

RUN docker-php-ext-install zip pdo_pgsql mbstring exif
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

RUN groupadd -g 1000 developer
RUN useradd -u 1000 -ms /bin/bash -g developer user

# Copiar apenas composer.json e composer.lock primeiro
COPY --chown=user:developer composer.json composer.lock /var/www/

# Instalar dependências sem executar scripts
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copiar o resto dos arquivos
COPY --chown=user:developer . /var/www

# Agora executar os scripts pós-instalação (opcional - pode ser feito no entrypoint)
# RUN composer dump-autoload --optimize

COPY ./docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
