<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\NotificationLogService;
use App\Services\CustomerService;
use App\Factories\LoggerFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Configurações
$rabbit_host = $_ENV['RABBITMQ_HOST'];
$rabbit_port = $_ENV['RABBITMQ_PORT'];
$rabbit_user = $_ENV['RABBITMQ_USER'];
$rabbit_pass = $_ENV['RABBITMQ_PASS'];
$queueName  = 'order_status_updates';

$notification_log_service = new NotificationLogService();
$customer_service = new CustomerService();

// Logger estruturado
$logger = LoggerFactory::create('worker');

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
$channel->queue_declare($queueName, false, true, false, false);

echo "Escutando mensagens da fila '{$queueName}'...\n";

$callback = function (AMQPMessage $msg) use ($notification_log_service, $customer_service, $args_order_number, $logger) {
  $received_at = date('c');
  echo "Mensagem recebida: " . $msg->body . "\n";

  try {
    $data = json_decode($msg->body, true);
    $msg_order_number = $data['order_id'];

    // Log recebimento
    $logger->info("Mensagem recebida da fila", [
      'timestamp' => $received_at,
      'service' => 'worker',
      'order_id' => $msg_order_number,
      'status_from' => $data['old_status'] ?? null,
      'status_to' => $data['new_status'] ?? null,
      'trace_id' => $data['trace_id'] ?? null,
      'request_id' => $data['request_id'] ?? null,
    ]);

    if ($args_order_number == $msg_order_number) {
      $created_at = date('c');

      $notification_log_service->createNotificationLog([
        'order_id'   => $data['order_id'],
        'old_status' => $data['old_status'],
        'new_status' => $data['new_status'],
        'timestamp' => $data['timestamp']
      ]);

      $customer = $customer_service->getByOrderNumber($data['order_id']);
      $email = $customer->email;

      echo "[$created_at] INFO: Order {$msg_order_number} status changed from {$data['old_status']} to {$data['new_status']}\n";
      echo "[$created_at] INFO: Notification sent to customer {$email}\n";

      // Log processamento da mensagem
      $logger->info("Mensagem processada com sucesso", [
        'timestamp' => $created_at,
        'service' => 'worker',
        'order_id' => $msg_order_number,
        'status_from' => $data['old_status'],
        'status_to' => $data['new_status'],
        'user_id' => $customer->id ?? null,
        'trace_id' => $data['trace_id'] ?? null,
        'request_id' => $data['request_id'] ?? null,
      ]);
      
      // confirma que a mensagem foi processada
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    } else {
      echo "Mensagem não é para este worker (esperado {$args_order_number}, veio {$msg_order_number}) → devolvendo.\n";
      // devolve para a fila
      $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
    }
  } catch (\Exception $e) {
    echo "Erro ao criar log: " . $e->getMessage() . "\n";

    // Log erro
    $logger->error("Erro ao processar mensagem", [
      'timestamp' => date('c'),
      'service' => 'worker',
      'order_id' => $data['order_id'] ?? null,
      'error' => $e->getMessage(),
      'trace_id' => $data['trace_id'] ?? null,
      'request_id' => $data['request_id'] ?? null,
    ]);
    
    // requeue para tentar novamente
    $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
  }
};

// Consumidor
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
  $channel->wait();
}

$channel->close();
$connection->close();

// Loop infinito com reconexão em caso de erro
while (true) {
  try {
    $channel->wait();
  } catch (\PhpAmqpLib\Exception\AMQPConnectionClosedException $e) {
    echo "Conexão perdida. Reconectando...\n";
    $connection = connectRabbit($rabbit_host, $rabbit_port, $rabbit_user, $rabbit_pass);
    $channel = $connection->channel();
    $channel->queue_declare($queueName, false, true, false, false);
  } catch (\PhpAmqpLib\Exception\AMQPDataReadException $e) {
    echo "Erro de leitura. Reconectando...\n";
    $connection = connectRabbit($rabbit_host, $rabbit_port, $rabbit_user, $rabbit_pass);
    $channel = $connection->channel();
    $channel->queue_declare($queueName, false, true, false, false);
  } catch (\Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
    sleep(3);
  }
}
