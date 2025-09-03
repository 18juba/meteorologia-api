FROM php:8.2-cli-alpine

# Instalar dependências essenciais
RUN apk add --no-cache \
    mysql-client \
    && docker-php-ext-install pdo pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www

# Copiar arquivos da aplicação
COPY . .

# Instalar dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Configurar permissões
RUN chmod -R 755 /var/www/storage

EXPOSE 8011

# Script de inicialização
COPY <<EOF /start.sh
#!/bin/sh
php artisan serve --host=0.0.0.0 --port=8011
EOF

RUN chmod +x /start.sh

CMD ["/start.sh"]