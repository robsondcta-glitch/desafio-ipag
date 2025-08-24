<?php

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addRoutingMiddleware();

$app->get('/', function($request, $response) {
  $response->getBody()->write("API do Desafio Ipag está rodando 🚀");
  return $response;
});

$app->get('/ping', function($request, $response) {
  $payload = json_encode(["message" => "pong"], JSON_UNESCAPED_UNICODE);
  $response->getBody()->write($payload);
  return $response->withHeader('Content-Type', 'application/json');
});

// carregar routes de Orders
(require __DIR__ . '/../routers/orderRoutes.php')($app);
(require __DIR__ . '/../routers/notificationLogRoutes.php')($app);

$app->run();
