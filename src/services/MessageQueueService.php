<?php

namespace APP\Services;

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/../../vendor/autoload.php';

class MessageQueueService {
  private $connection;
  private $channel;
  // private $queue = 'order_status_updates';

  public function __construct() {
    // Carregar variáveis do .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    $this->connection = new AMQPStreamConnection(
      $_ENV['RABBITMQ_HOST'], 
      $_ENV['RABBITMQ_PORT'], 
      $_ENV['RABBITMQ_USER'], 
      $_ENV['RABBITMQ_PASS']
    );
    $this->channel = $this->connection->channel();
  }

  public function publish($data) {
    $msg = new AMQPMessage(json_encode($data));
    $this->channel->queue_declare('order_status_updates', false, true, false, false);
    $this->channel->basic_publish($msg, '', 'order_status_updates');
  }

  public function __destruct() {
    $this->channel->close();
    $this->connection->close();
  }
}
