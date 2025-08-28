# 📘 Desafio-ipag

API de pedidos com mensageria via RabbitMQ, desenvolvida em PHP (Slim), utilizando MySQL e Docker Compose.

## 🚀 Começando

Essas instruções mostram como configurar e executar o projeto localmente com Docker Compose, rodar migrations e realizar testes básicos.

---

## 📋 Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) e [Docker Compose](https://docs.docker.com/compose/) instalados.  
- No Windows, é necessário habilitar o WSL2 e utilizar o Docker Desktop.  

---

## 🏗 Arquitetura

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│   API REST  │────│   RabbitMQ   │────│   Worker    │
│             │    │              │    │             │
│ - Orders    │    │ Queue:       │    │ - Consume   │
│ - Status    │    │ order_status │    │ - Log       │
│ - Summary   │    │              │    │ - Notify    │
└─────────────┘    └──────────────┘    └─────────────┘
       │                                       │
       └─────────────┐               ┌─────────┘
                     │               │
              ┌──────▼───────────────▼──────┐
              │      MySQL Database         │
              │                             │
              │ Tables:                     │
              │ • customers                 │
              │ • orders                    │
              │ • order_items               │
              │ • notification_logs         │
              └─────────────────────────────┘
```

A API REST feita utilizando o php (Slim), recebe o pedidos e salva as informações no banco de dados. 
Em seguida, ao atualizar as informações de status, será gerado uma notificação ao qual é enviada ao RabbitMQ com a fila chamada **'order_status_updates'**, a mesma será responsável de encaminhar a mensagem para os Workers. 
Os workers consomem essas mensagens e processam apenas as que correspondem ao seu pedido.

---

## 🐳 Subindo com Docker Compose

**Arquivo `docker-compose.yml`:**
```yaml
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

**Arquivo `Dockerfile`:**
```dockerfile

FROM php:8.2-apache
RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql bcmath sockets
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
ENV APACHE_DOCUMENT_ROOT /var/www/html/src/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/sites-enabled/*.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html

```

### Comandos principais

Subir containers:
```bash
docker-compose up -d
```

Verificar logs:
```bash
docker-compose logs -f
```

Parar containers:
```bash
docker-compose down
```

---

## ⚙️ Variáveis de Ambiente

O projeto utiliza um arquivo `.env` baseado no `.env.example`.

```
DB_HOST=mysql
DB_PORT=3306
DB_NAME=ipag
DB_USER=root
DB_PASS=root123

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASS=guest
```

As variáveis iniciadas com **DB_** configuram a conexão com o banco de dados e devem corresponder aos valores do docker-compose.yml.
As variáveis iniciadas com **RABBITMQ_** configuram o microserviço do RabbitMQ e também devem corresponder ao docker-compose.yml.

## 🗄️ Migrations

Para criar as tabelas definidas:

```bash
docker exec -it desafio_ipag_php php src/database/migrate.php
```

Este comando criará as tabelas e configurações conforme os arquivos que foram especificados dentro da pasta localizada no **src/database**, o arquivo migrate.php fará a execução dos arquivos de criação de tabelas que estão localizados no **src/database/migrations**. As migrations podem ser executadas novamente, caso tenha acontecido algum erro durante sua execução.

---

## 📦 Dependências

Para ter acesso as funcionalidades do sistema, será necessário instalar as dependências do projeto. Para isso, siga os passos abaixo:

Instale com o Composer (local ou dentro do container):

```bash
composer install
```

Se precisar recriar utilize o autoload:
```bash
composer dump-autoload
```

---

## 🧵 Worker

O worker é responsável pro consumir as mensagens da fila enviadas pelo RabbitMQ e gerar logs. Para ter acesso a essa funcionalidade, siga os passos abaixo:

Entrar no container do PHP:
```bash
docker exec -it desafio_ipag_php bash
```

Rodar worker:
```bash
cd /var/www/html/src/worker
php worker.php "order_number"
```

---

## 🌐 Exemplos de Uso (curl/Postman)

### 1. Criar um Pedido

**Endpoint**
```
POST /orders
```

**Descrição**
Cria um novo pedido associado a um cliente, incluindo os itens da compra e o valor total.

**Exemplo de Requisição**
```
curl -X POST http://localhost:8080/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "id": 1,
      "name": "Fulano de Tal",
      "document": "12345678900",
      "email": "fulano@email.com",
      "phone": "11999999999"
    },
    "order": {
      "total_value": 150.00,
      "items": [
        {
          "product_name": "Produto 1",
          "quantity": 2,
          "unit_value": 50.00
        },
        {
          "product_name": "Produto 2",
          "quantity": 1,
          "unit_value": 50.00
        }
      ]
    }
  }'
```

**Exemplo de Resposta**
```
{
  "order_id": "ORD-41649",
  "order_number": "ORD-41649",
  "status": "PENDING",
  "total_value": 150,
  "customer": {
    "id": 1,
    "name": "Fulano de Tal",
    "document": "12345678900",
    "email": "fulano@email.com",
    "phone": "11999999999"
  },
  "items": [
    {
      "product_name": "Produto 1",
      "quantity": 2,
      "unit_value": 50,
      "total_value": 100
    },
    {
      "product_name": "Produto 2",
      "quantity": 1,
      "unit_value": 50,
      "total_value": 50
    }
  ],
  "created_at": "2025-08-28T00:11:59+00:00"
}
```

### 2. Atualizar Status do Pedido

**Endpoint**
```
PUT /orders/{order_id}/status
```

**Descrição**
Atualiza o status de um pedido existente

**Exemplo de Requisição**
```
curl -X PUT http://localhost:8080/orders/ORD-41649/status \
  -H "Content-Type: application/json" \
  -d '{
    "status": "PAID"
  }'
```

**Exemplo de Resposta**
```
{
  "status": "PAID",
  "notes": "Pagamento confirmado"
}
```

### 3. Listar Pedidos

**Endpoint**
```
GET /orders
```

**Descrição**
Retorna a lista de pedidos cadastrados no sistema.
Permite aplicar filtros opcionais por status, cliente e intervalo de datas.

Parâmetros de Filtro (Query Params)
**status**: do tipo **string**, que filtra pedidos por status (PENDING, WAITING_PAYMENT, PAID, PROCESSING, SHIPPED, DELIVERED, CANCELED).
**customer_id**: do tipo **integer**, que filtra os pedidos por um cliente especifico.
**start_date**: do tipo **string**, data inicial no formato **YYYY-MM-DD**.
**end_date**: do tipo **string**, data final no formato **YYYY-MM-DD**.

**Exemplo de Requisição (sem filtro)**
```
curl -X GET http://localhost:8080/orders
```

**Exemplo de Requisição (com filtro)**
```
curl -X GET "http://localhost:8080/orders?status=PAID&customer_id=1&start_date=2025-08-01&end_date=2025-08-28"
```

**Exemplo de Resposta**
```
[
  {
    "id": 1,
    "customer_id": 1,
    "order_number": "ORD-89722",
    "total_value": "150.00",
    "status": "PENDING",
    "created_at": "2025-08-24 18:07:28",
    "updated_at": "2025-08-24 18:07:28"
  }
]
```

### 4. Consultar um Pedido Específico

**Endpoint**
```
GET /orders/{order_number}
```

**Descrição**
Busca os detalhes de um pedido específico pelo número do pedido.

**Exemplo de Requisição**
```
curl -X GET http://localhost:8080/orders/ORD-52062
```

**Exemplo de Resposta**
```
{
  "id": 10,
  "customer_id": 1,
  "order_number": "ORD-52062",
  "total_value": "150.00",
  "status": "PAID",
  "created_at": "2025-08-24 18:15:10",
  "updated_at": "2025-08-28 00:24:22"
}
```

### 5. Resumo dos Pedidos

**Endpoint**
```
GET /orders/summary
```

**Descrição**
Retorna um resumo geral dos pedidos, incluindo quantidade, receita total, ticket médio, valores mínimo e máximo, além do status agregado das ordens.

**Exemplo de Requisição**
```
curl -X GET http://localhost:8080/orders/summary
```

**Exemplo de Resposta**
```
{
  "total_orders": 11,
  "total_revenue": "1650.00",
  "avg_ticket": "150.00",
  "min_order_value": "150.00",
  "max_order_value": "150.00",
  "total_customers": 1,
  "pending_orders": "10",
  "paid_orders": "1",
  "shipped_orders": "0",
  "delivered_orders": "0",
  "canceled_orders": "0"
}
```

### 6. Mensageria (RabbitMQ)

O RabbitMQ é um microserviço de mensageria responsável por encaminhar mensagens em filas. Esta funcionalidade será acionada sempre que o status de algum pedido mudar, sendo eles **PENDING**, **WAITING_PAYMENT**, **PAID**, **PROCESSING**, **SHIPPED**, **DELIVERED** e **CANCELED**.
Quando o status muda, uma mensagem é enviada para a fila `order_status_updates`.  
Para acessar e visualizar em um modelo visual, acesse via **RabbitMQ Management UI**: [http://localhost:15672](http://localhost:15672) (user: guest/ pass: guest).

### 7. Processar Mensagem com Worker

O Worker é responsável por coletar as mensagens disponibilizadas na fila `order_status_updates` e processá-la. Para utilizar o worker execute o comando abaixo:

```bash
docker exec -it desafio_ipag_php php /var/www/html/src/worker/worker.php ORD-52062
```

### 8. Logs (Monolog)

Responsável por armazenar os dados do log do sistema.

**Local:**
```
/var/www/html/storage/logs/app.log
```

**Exemplo de log:**
```
[2025-08-28 10:30:12] order.INFO: Pedido processado {"order_id":"ORD-52062","status":"PAID"}
```

### 💡 Fluxo Completo
1. Criar pedido (`POST /orders`)  
2. Atualizar status (`PUT /orders/{order_id}/status`)  
3. Mensagem enviada para RabbitMQ  
4. Worker processa fila  
5. Monolog registra log  

---

## 📝 Decisões Técnicas

- **PHP** → PHP 8.2 (por familiaridade e flexibilidade). 
- **Slim Framework** → Microframework leve e que atende as demandas para a criação de uma aplicação APIRest.  
- **Banco de Dados:** → MySQL 8
- **Acesso ao Banco de Dados:** → PDO puro com migrations manuais. 
- **RabbitMQ** → Mensageria robusta para desacoplar processos.  
- **Docker Compose** → Facilita a orquestração dos serviços.  
- **Monolog** → Padrão de mercado para logging estruturado.  
