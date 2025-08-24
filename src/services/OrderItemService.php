<?php

namespace App\Services;

use App\Repositories\OrderItemRepository;
use App\Models\OrderItem;

class OrderItemService {

  private $repo;

  public function __construct() {
    $this->repo = new OrderItemRepository();
  }

  public function createOrderItem($data) {
    $order_item = new OrderItem($data);
    return $this->repo->create($order_item);
  }

  public function getByOrderId($order_id) {
    $order_itens = $this->repo->findByOrderId($order_id);
    if (!$order_itens || count($order_itens) == 0)
      throw new \Exception("Order items not found");
    return $order_itens;
  }
}