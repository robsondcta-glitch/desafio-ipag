<?php 

namespace App\Repositories;

use App\Models\OrderItem;

require_once __DIR__ . '/../config/database.php';

class OrderItemRepository {
  public function create($order_item) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_name, quantity, unit_value) 
      VALUES (:order_id, :product_name, :quantity, :unit_value");
    $stmt->execute([
      ':order_id' => $order_item->order_id,
      ':product_name' => $order_item->product_name,
      ':quantity' => $order_item->quantity,
      ':unit_value' => $order_item->unit_value,
    ]);
    $order_item->id = $pdo->lastInsertId();
    $pdo = null;
    return $order_item;
  }

  public function findByOrderId($order_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll();
    $pdo = null;
    return array_map(fn($data) => new OrderItem($data), $order_items);
  }
}