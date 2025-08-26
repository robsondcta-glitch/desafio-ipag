# Dockerfile
FROM php:8.2-apache

# Instalar extensões necessárias (ex.: pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# habilita mod_rewrite já feito antes
RUN a2enmod rewrite

# permite leitura de .htaccess (substitui AllowOverride None -> All)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar todo o projeto para dentro do container
COPY . /var/www/html/

# Configurar document root para a pasta public do Slim
ENV APACHE_DOCUMENT_ROOT /var/www/html/src/public

# Atualizar configuração do Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Ajustar permissões
RUN chown -R www-data:www-data /var/www/html

# instala a extensão BCMath do PHP dentro do container
RUN docker-php-ext-install bcmath

