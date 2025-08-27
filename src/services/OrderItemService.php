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
    $order_items = $this->repo->findByOrderId($order_id);
    if (empty($order_items) || count($order_items) == 0) {
      throw new \Exception("Order items not found");
    }
    return $order_items;
  }
}