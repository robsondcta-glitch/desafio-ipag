## Desafio-ipag

## 🚀 Começando

Essas instruções permitirão que você consiga uma cópia do projeto em sua máquina local para análise da implementação da solução e realização de testes.

## 📋 Pré-requisitos

Caso esteja executando em um ambiente Windows precisará realizar a instalação do WSL 2 e o Docker Desktop.
Quando criar o ambiente de desenvolvimento, será instalado os containers do PHP 8, MySQL e RabbitMQ.

**docker-compose.yml**

```
version: "3.8"

services:
  php:
    build: .
    container_name: desafio_ipag_php
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
      - rabbitmq

  mysql:
    image: mysql:8.0
    container_name: desafio_ipag_mysql
    environment:
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_DATABASE: ipag
      MYSQL_USER: ipaguser
      MYSQL_PASSWORD: ipagpass
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  rabbitmq:
    image: rabbitmq:3-management
    container_name: desafio_ipag_rabbitmq
    ports:
      - "5672:5672"
      - "15672:15672"
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest  

volumes:
  mysql_data:
```

**Dockerfile**

```
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

```

**Subindo o ambiente**

No terminal, acesse a pasta do projeto e execute o comando abaixo:

```
docker-compose up -d
```

Para verificar se tudo funcionou corretamente e os container estão ativos, utilize o comando abaixo:

```
docker-compose logs -f
```

Caso queira em algum momento parar a execuçaõ do container, utilize o comando:

```
docker-compose down
```

## 1. Testes Iniciais

API: http://localhost:8080 → deve exibir index.php

MySQL: localhost:3306 (usuário: ipaguser / senha: ipagpass)

RabbitMQ Management UI: http://localhost:15672 (usuário: guest / senha: guest)

Essas informações de acesso estão registradas no arquivo docker-compose.yml, caso você tenha trocado alguma informação de acesso, login, senha ou porta, lembre-se de utilizar as informações que foram escolhidas.

## 2. Criação das tabelas

Para criar as tabelas que foram definidas nas migrations, utilize o comando abaixo:

```
docker exec -it desafio_ipag_php php src/database/migrate.php
```

Atente-se de que: Caso tenha alterado o usuário e senha, os mesmos deverão ser informados no arquivo de configuração da **database**

## 3. Faça a instalação do Composer

Você pode tanto fazer a instalação na maquina local ou no container. No caso deste projeto, foi realizado as instalações e execuções diretamente do container do php **"desafio_ipag_php"**.
A instalação do Composer é necessária para poder realizar o download das dependências do projeto. Para isso abra o terminal na pasta raiz do projeto e execute o comando abaixo:

```
composer install
```

## Utilização do Worker

Para executar o worker, precisa acessar o php do docker

```
docker exec -it desafio_ipag_php bash
```

Depois se dirigir a pasta

```
cd /var/www/html/src/worker
```

Para executar o codigo utilize o comando

```
php worker.php "order_number"
```

Caso de um problema de execução do worker, informando que as bibliotecas não foram localizadas, siga os passos abaixo:

Na pasta raiz do acesso ao docker 

```
/var/www/html
```

Executa o comando 

```
composer dump-autoload
```

Que irá fazer o vinculo das dependencias instaladas.

