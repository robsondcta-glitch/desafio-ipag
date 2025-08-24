<?php

use Slim\App;
use App\Controllers\NotificationLogController;

return function (App $app) {
  $notification_log_controller = new NotificationLogController();

  // Criar pedido
  $app->post('/notification_log', [$notification_log_controller, 'create']);

  // Consultar pedido específico
  $app->get('/notification_log/{order_id}', [$notification_log_controller, 'getByOrderId']);
};
