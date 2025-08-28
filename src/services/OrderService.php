<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Services\CustomerService;
use App\Services\OrderItemService;
use App\Services\MessageQueueService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Factories\LoggerFactory;

class OrderService {

  private $repo;
  private $logger;

  public function __construct() {
    $this->repo = new OrderRepository();
    $this->logger = LoggerFactory::create('api');
  }
  
  public function createOrder($data) {
    $pdo = getPDO();
    try {
      // Inicia a transação
      $pdo->beginTransaction();

      $customer_data = $data['customer'];
      $order_data = $data['order'];

      // criar ou validar cliente
      $customer_service = new CustomerService();
      if (!isset($customer_data['id'])) { // cliente novo
        $customer = $customer_service->createCustomer($customer_data);
      } else { // cliente existente
        // Verifica se o cliente já existe, caso não, cria
        if (!$customer_service->verifyCustomer($customer_data['id'])) {
          $customer = $customer_service->createCustomer($customer_data);
        } else {
          $customer = $customer_service->getById($customer_data['id']);
        }
      }
      
      $tries = 0;
      do {
        // Gera o order number randomico
        $order_number = 'ORD-' . rand(10000, 99999);
        $tries++;
        // utiliza o verifyOrderNumber para verificar se o número da ordem já existe
      } while (!$this->repo->verifyOrderNumber($order_number) && $tries < 10);

      if ($tries >= 10) {
        throw new \Exception("Não foi possível gerar um número de ordem único");
      }

      // Caso o valor total do pedido não tenha sido informado 
      $total_value = $order_data['total_value'] ?? 0;
      if ($total_value == 0) {
        foreach ($order_data['items'] as $item) {
          $total_value += $item['quantity'] * $item['unit_value'];
        }
      }

      // criar o pedido
      $order = new Order([
        'customer_id' => $customer->id,
        'order_number' => $order_number,
        'total_value' => $total_value,
        'status' => 'PENDING'
      ]);

      $order = $this->repo->create($order);

      $order_item_service = new OrderItemService();
      $items = [];

      foreach ($order_data['items'] as $item) {
        $order_item = new OrderItem([
          'order_id' => $order->id,
          'product_name' => $item['product_name'],
          'quantity' => $item['quantity'],
          'unit_value' => $item['unit_value'],
        ]);

        $saved_order_item = $order_item_service->createOrderItem($order_item);
        
        $items[] = [
          'product_name' => $saved_order_item->product_name,
          'quantity' => $saved_order_item->quantity,
          'unit_value' => $saved_order_item->unit_value,
          'total_value' => $saved_order_item->quantity * $saved_order_item->unit_value,
        ];
      }

      // Cria o log do create order
      $this->logger->info("Pedido criado com sucesso", [
        'timestamp'   => date(DATE_ATOM),
        'service'     => 'api',
        'trace_id'    => $data['request_id'] ?? null,
        'request_id'  => $data['request_id'] ?? null,
        'user_id'     => $customer->id ?? null,
        'order_id'    => $order->order_number,
        'status_from' => null,
        'status_to'   => 'PENDING'
      ]);

      // Confirma a transação
      $pdo->commit();

      return [
        'order_id' => $order->order_number,
        'order_number' => $order->order_number,
        'status' => $order->status,
        'total_value' => $order->total_value,
        'customer' => [
          'id' => $customer->id,
          'name' => $customer->name,
          'document' => $customer->document,
          'email' => $customer->email,
          'phone' => $customer->phone,
        ],
        'items' => $items,
        'created_at' => date('c')
      ];
    } catch (\Exception $e) {
      // Se der erro, faz rollback
      $pdo->rollBack();

       $this->logger->error("Erro ao criar pedido", [
        'timestamp'   => date(DATE_ATOM),
        'service'     => 'api',
        'trace_id'    => $data['request_id'] ?? null,
        'request_id'  => $data['request_id'] ?? null,
        'user_id'     => $customer_data['id'] ?? null,
        'message'     => $e->getMessage(),
      ]);

      throw new \Exception("Ocorreu um erro ao salvar os dados do pedido, por favor, tente novamente.");
    } finally {
      $pdo = null;
    }
  }

  public function changeStatus($order_number, $new_status) {
    $order = $this->repo->findByOrderNumber($order_number);
    if (empty($order)) {
      throw new \Exception("Order not found");
    }

    // aqui podemos validar transições de status
    $valid_next_status = [
      'PENDING' => [
        'message' => 'Pedido criado, aguardando pagamento',
        'next'    => ['WAITING_PAYMENT', 'CANCELED']
      ],
      'WAITING_PAYMENT' => [
        'message' => 'Aguardando confirmação de pagamento',
        'next'    => ['PAID', 'CANCELED']
      ],
      'PAID' => [
        'message' => 'Pagamento confirmado',
        'next'    => ['PROCESSING', 'CANCELED']
      ],
      'PROCESSING' => [
        'message' => 'Pedido em processamento',
        'next'    => ['SHIPPED', 'CANCELED']
      ],
      'SHIPPED' => [
        'message' => 'Pedido enviado',
        'next'    => ['DELIVERED', 'CANCELED']
      ],
      'DELIVERED' => [
        'message' => 'Pedido entregue ao cliente',
        'next'    => []
      ],
      'CANCELED' => [
        'message' => 'Pedido cancelado',
        'next'    => []
      ]
    ];

    if (!in_array($new_status, $valid_next_status[$order->status]['next'])) {
      throw new \Exception("Inválida mudança de status de {$order->status} para $new_status");
    }
    
    $old_status_order = $order->status;
    
    $this->repo->updateStatusByOrderNumber($order_number, $new_status);

    // Log da mudança de status
    $this->logger->info("Mudança de status do pedido", [
      'timestamp'   => date(DATE_ATOM),
      'service'     => 'api',
      'trace_id'    => $correlationId ?? null,
      'request_id'  => $correlationId ?? null,
      'user_id'     => 'system',
      'order_id'    => $order_number,
      'status_from' => $old_status_order,
      'status_to'   => $new_status,
    ]);

    // Publica a mudança de status para o worker através do RabbitMQ
    $mq = new MessageQueueService();
    $mq->publish([
      'order_id' => $order_number,
      'old_status' => $old_status_order,
      'new_status' => $new_status,
      'timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
      'user_id' => 'system',
    ]);

    $this->logger->info("Mensagem enviada para RabbitMQ", [
      'timestamp'       => date(DATE_ATOM),
      'service'         => 'api',
      'correlation_id'  => $correlationId ?? null,
      'order_id'        => $order_number,
      'status_from'     => $old_status_order,
      'status_to'       => $new_status
    ]);

    return [
      'status' => $new_status,
      'notes' => $valid_next_status[$new_status]['message'],
    ];
  }

  public function getByOrderNumber($order_number) {
    $order = $this->repo->findByOrderNumber($order_number);
    if (empty($order)) {
      throw new \Exception("Order not found");
    }
    return $order;
  }

  public function listOrders($status = null, $customer_id = null, $start_date = null, $end_date = null) {
    $orders = $this->repo->findAll($status, $customer_id, $start_date, $end_date);
    return $orders;
  }

  public function getSummary() {
    return $this->repo->getSummary();
  }
}
