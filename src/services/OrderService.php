<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Services\CustomerService;
use App\Services\OrderItemService;
use App\Services\MessageQueueService;
use App\Models\Order;
use App\Models\OrderItem;

class OrderService {

  private $repo;

  public function __construct() {
    $this->repo = new OrderRepository();
  }
  
  public function createOrder($data) {
    $pdo = getPDO();
    try {
      // Inicia a transação
      $pdo->beginTransaction();

      $customer_data = $data['customer'];
      $order_data = $data['order'];

      // criar ou validar cliente
      $customerService = new CustomerService();
      if (!isset($customer_data['id'])) { // cliente novo
        $customer = $customerService->createCustomer($customer_data);
      } else { // cliente existente
        // Verifica se o cliente já existe, caso não, cria
        if (!$customerService->verifyCustomer($customer_data['id'])) {
          $customer = $customerService->createCustomer($customer_data);
        } else {
          $customer = $customerService->getById($customer_data['id']);
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
      throw new \Exception("Ocorreu um erro ao salvar os dados do pedido, por favor, tente novamente.");
    } finally {
      $pdo = null;
    }
  }

  public function changeStatus($order_number, $new_status) {
    $order = $this->repo->findByOrderNumber($order_number);
    if (!$order) {
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


    // Busca o customer pelo order number
    $customerService = new CustomerService();
    $customer = $customerService->getByOrderId($order->id);
    $old_status_order = $order->status;
    
    $this->repo->updateStatusByOrderNumber($order_number, $new_status);

    // Publica a mudança de status para o worker através do RabbitMQ
    $mq = new MessageQueueService();
    $mq->publish([
      'email' => $customer->email,
      'order_id' => $order->id,
      'order_number' => $order_number,
      'old_status' => $old_status_order,
      'new_status' => $new_status,
      'message' => $valid_next_status[$new_status]['message'],
    ]);

    return [
      'status' => $new_status,
      'notes' => $valid_next_status[$new_status]['message'],
    ];
  }

  public function getByOrderNumber($order_number) {
    $order = $this->repo->findByOrderNumber($order_number);
    if (!$order) {
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
