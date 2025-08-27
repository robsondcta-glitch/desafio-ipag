<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\NotificationLogService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Configurações

$rabbit_host = $_ENV['RABBITMQ_HOST'];
$rabbit_port = $_ENV['RABBITMQ_PORT'];
$rabbit_user = $_ENV['RABBITMQ_USER'];
$rabbit_pass = $_ENV['RABBITMQ_PASS'];
$queue_name  = 'order_status_updates';

$notification_service = new NotificationLogService();

// Função para conectar ao RabbitMQ com retry
function connectRabbit($host, $port, $user, $pass) {
  while (true) {
    try {
      $connection = new AMQPStreamConnection($host, $port, $user, $pass);
      echo "Conectado ao RabbitMQ!\n";
      return $connection;
    } catch (\Exception $e) {
      echo "Não conseguiu conectar: {$e->getMessage()}. Tentando novamente em 3 segundos...\n";
      sleep(3);
    }
  }
}

// Pega o order number enviado para o worker
$args_order_number = $argv[1] ?? null;

if (!$args_order_number) {
  die("Informe o número da ordem como argumento: php worker.php $args_order_number\n");
}

echo "Escutando mensagens da ordem {$args_order_number}. CTRL+C para sair\n";

// Conecta
$connection = connectRabbit($rabbit_host, $rabbit_port, $rabbit_user, $rabbit_pass);
$channel = $connection->channel();

// Declara fila (se não existir)
$channel->queue_declare($queue_name, false, true, false, false);

echo "Escutando mensagens da fila '{$queue_name}'...\n";

$callback = function (AMQPMessage $msg) use ($notification_service, $args_order_number) {
  echo "Mensagem recebida: " . $msg->body . "\n";

  try {
    $data = json_decode($msg->body, true);

    $msg_order_number = $data['order_number'];

    if ($args_order_number == $msg_order_number) {
      $created_at = date('Y-m-d H:i:s');

      $notification_service->createNotificationLog([
        'order_id'   => $data['order_id'],
        'old_status' => $data['old_status'],
        'new_status' => $data['new_status'],
        'message' => $data['message'],
        'created_at' => $created_at,
      ]);
        
      $email = $data['email'] ?? 'sem-email';
      echo "[$created_at] INFO: Order {$msg_order_number} status changed from {$data['old_status']} to {$data['new_status']}\n";
      echo "[$created_at] INFO: Notification sent to customer {$email}\n";
    }
  } catch (\Exception $e) {
    echo "Erro ao criar log: " . $e->getMessage() . "\n";
  }
};

// Consumidor
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
  $channel->wait();
}

$channel->close();
$connection->close();
