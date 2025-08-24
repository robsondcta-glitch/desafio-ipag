<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Models\Order;

class OrderService {

  private $repo;

  public function __construct() {
    $this->repo = new OrderRepository();
  }

  // public function createOrder($customerId, $totalValue) {
  //   $orderNumber = 'ORD-' . rand(10000, 99999);
  //   $order = new Order([
  //     'customer_id' => $customerId,
  //     'order_number' => $orderNumber,
  //     'total_value' => $totalValue,
  //     'status' => 'PENDING'
  //   ]);

  //   return $this->repo->create($order);
  // }

  public function createOrder($customer_data, $order_data) {
    // criar ou validar cliente
    if (!isset($customer_data['id'])) {
      // cliente novo
      $customerService = new \App\Services\CustomerService();
      $customer = $customerService->createCustomer($customer_data);
    } else {
      // cliente existente
      $customerService = new \App\Services\CustomerService();
      $customer = $customerService->getById($customer_data['id']);
    }

    // criar o pedido
    $orderNumber = 'ORD-' . rand(10000, 99999);
    $order = new \App\Models\Order([
      'customer_id' => $customer->id,
      'order_number' => $orderNumber,
      'total_value' => $order_data['total_value'],
      'status' => 'PENDING'
    ]);

    $this->repo->create($order);

    return [
      'order' => $order,
      'customer' => $customer
    ];
  }

  public function changeStatus($orderId, $newStatus) {
    $order = $this->repo->findById($orderId);
    if (!$order) {
      throw new \Exception("Order not found");
    }

    // aqui podemos validar transições de status
    $validNextStatuses = [
      'PENDING' => ['WAITING_PAYMENT', 'CANCELED'],
      'WAITING_PAYMENT' => ['PAID', 'CANCELED'],
      'PAID' => ['PROCESSING', 'CANCELED'],
      'PROCESSING' => ['SHIPPED', 'CANCELED'],
      'SHIPPED' => ['DELIVERED', 'CANCELED'],
      'DELIVERED' => [],
      'CANCELED' => []
    ];

    if (!in_array($newStatus, $validNextStatuses[$order->status])) {
      throw new \Exception("Invalid status transition from {$order->status} to $newStatus");
    }

    $this->repo->updateStatus($orderId, $newStatus);
    return $this->repo->findById($orderId);
  }

  public function getById($orderId) {
    $order = $this->repo->findById($orderId);
    if (!$order) throw new \Exception("Order not found");
    return $order;
  }

  public function listOrders() {
    return $this->repo->findAll(); // implementar findAll() no OrderRepository
  }

  public function getSummary() {
    return $this->repo->getSummary(); // implementar getSummary() no OrderRepository
  }
}
