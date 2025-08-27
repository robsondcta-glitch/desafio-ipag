<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

try {
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    echo "Conexão com RabbitMQ OK!\n";
    $connection->close();
} catch (\Exception $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}
