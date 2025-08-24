# Plano de Trabalho — Desafio iPag

**Ambiente de desenvolvimento:** Windows 11 (WSL2), Docker Desktop 4.33, PHP 8.2, Composer 2.7  
**Faixa de esforço prevista:** 20–35 horas (estimativa atual: 28h)

---

## 1. Objetivo

O objetivo projeto consiste em desenvolver uma **API REST em PHP** para gerenciar os pedidos de venda, integrado com o **banco de dados MySQL**, que será responsável por armazenar as informações e utilizando o **RabbitMQ**, para o processamento assíncrono das notificações de status. 

Também será implementado um **Worker**, um microserviço responsável por consumir as mensagens enviadas pelo **RabbitMQ**, registrar logs e simular o envio de notificações. 

Toda a solução será disponibilizada em um repositório público no **GitHub**, rodando via **Docker Compose**, com documentação de instalação, execução e exemplos de uso.

---

## 2. Escopo do Projeto

### Componentes
- **API REST (PHP + Slim 4)**  
  - CRUD de pedidos  
  - Atualização de status baseando nas regras de negócio  
  - Resumo estatístico de pedidos  
  - Publicação de mensagens no RabbitMQ  

- **Banco de Dados (MySQL)**  
  - customers  
  - orders  
  - order_items  
  - notification_logs  

- **Mensageria (RabbitMQ)**  
  - Fila `order_status_updates`  
  - Comunicação entre API e Worker  

- **Worker (PHP CLI)**  
  - Consumir mensagens da fila  
  - Registrar logs no banco  
  - Simular envio de notificações  

---

## 3. Decisões Técnicas

- **Linguagem:** PHP 8.2 (por familiaridade e flexibilidade).  
- **Framework:** Slim 4 (microframework leve e que atende as demandas para a criação de uma aplicação APIRest).  
- **Banco de Dados:** MySQL 8 (por familiaridade).  
- **Mensageria:** RabbitMQ (requisito obrigatório).  
- **Acesso ao Banco de Dados:** PDO puro com migrations manuais.  
- **Ambiente:** Docker Compose para orquestração.  
- **Padrões adotados:**  
  - MVC (Controllers, Models, Repositories)  
  - Repository Pattern para acesso ao banco  
  - Código organizado em camadas para clareza e manutenibilidade  

---

## 4. Entregáveis

- Código fonte completo da solução.  
- **README.md** detalhado com instruções de setup e uso.  
- **Migrations SQL** para criação das tabelas.  
- **Docker Compose** para subir a stack completa.  
- Worker funcional para consumo da fila.  

---

## 5. Estimativa de Atividades

| Atividade | Descrição | Estimativa |
|-----------|-----------|------------|
| **1. Preparação do ambiente** | Configuração do repositório, Docker Compose, containers PHP/MySQL/RabbitMQ | 3h |
| **2. Modelagem do Banco** | Definição das tabelas + migrations SQL | 2h |
| **3. Estrutura da API** | Configuração do Slim, organização MVC, endpoints base | 3h |
| **4. Endpoints de Pedidos** | POST /orders, GET /orders, GET /orders/{id} | 4h |
| **5. Atualização de Status** | PUT /orders/{id}/status + regras de negócio | 3h |
| **6. Integração RabbitMQ (Publisher)** | Publicação de mensagens ao alterar status | 3h |
| **7. Worker (Consumer)** | Implementação do consumo da fila + logs | 3h |
| **8. Resumo de Pedidos** | GET /orders/summary (estatísticas básicas) | 2h |
| **9. Testes manuais e ajustes** | Testes locais com Postman, correções | 3h |
| **10. Documentação Final** | Finalizar README, exemplos de uso, decisões técnicas | 2h |

**Total estimado:** 28 horas 

---

## 6. Cronograma

- **Dia 1-2:** Configuração do ambiente, Docker Compose, migrations  
- **Dia 3:** Endpoints básicos e atualização de status  
- **Dia 4:** RabbitMQ (Publisher) e Worker  
- **Dia 5:** Resumo de pedidos e testes manuais  
- **Dia 6:** Ajustes finais e documentação  
