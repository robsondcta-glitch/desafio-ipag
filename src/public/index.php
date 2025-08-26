<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addRoutingMiddleware();

$app->get('/', function($request, $response) {
  $response->getBody()->write("API do Desafio Ipag está rodando 🚀");
  return $response;
});

// carregar routes de Orders
(require __DIR__ . '/../routers/orderRoutes.php')($app);

$app->run();
