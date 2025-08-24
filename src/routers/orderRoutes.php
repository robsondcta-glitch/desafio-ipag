<?php

use Slim\App;
use App\Controllers\OrderController;

return function (App $app) {
  $orderController = new OrderController();

  // Criar pedido
  $app->post('/orders', [$orderController, 'create']);

  //  Listar pedidos
  $app->get('/orders', [$orderController, 'list']);

  // Resumo estatístico dos pedidos (deve vir antes da rota variável)
  $app->get('/orders/summary', [$orderController, 'summary']);

  // Consultar pedido específico
  $app->get('/orders/{order_id}', [$orderController, 'getById']);

  // Atualizar status do pedido
  $app->put('/orders/{order_id}/status', [$orderController, 'updateStatus']);
};
