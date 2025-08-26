<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use APP\Services\NotificationLogService;
use Dotenv\Dotenv;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Dados da conexão
$connection = new AMQPStreamConnection(
  $_ENV['RABBITMQ_HOST'],
  $_ENV['RABBITMQ_PORT'],
  $_ENV['RABBITMQ_USER'],
  $_ENV['RABBITMQ_PASS']
);

$channel = $connection->channel();

// Aqui você passa a ordem que quer monitorar
$args_order_number = $argv[1] ?? null;

if (!$args_order_number) {
  die("Informe o número da ordem como argumento: php worker.php $args_order_number\n");
}

$queue = "order_status_updates";
$channel->queue_declare($queue, false, true, false, false);

echo "Escutando mensagens da ordem {$args_order_number}. CTRL+C para sair\n";

$callback = function ($msg) use ($args_order_number) {
  $data = json_decode($msg->body, true);
  $msgOrderNumber = $data['order_number'];

  if ($args_order_number == $msgOrderNumber) {
    $created_at = date('Y-m-d H:i:s');

    $notification_log_service = new NotificationLogService();
    $notification_log_service->createNotificationLog([
      'order_id'   => $data['order_id'],
      'old_status' => $data['old_status'],
      'new_status' => $data['new_status'],
      'message'    => $data['message'],
      'created_at' => $created_at,
    ]);

    $email = $data['email'] ?? 'sem-email';
    echo "[$created_at] INFO: Order {$msgOrderNumber} status changed from {$data['old_status']} to {$data['new_status']}\n";
    echo "[$created_at] INFO: Notification sent to customer {$email}\n";
  }

  $msg->ack();
};

// Consumidor
$channel->basic_consume($queue, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
  $channel->wait();
}

$channel->close();
$connection->close();
