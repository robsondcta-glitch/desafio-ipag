<?php

namespace App\Repositories;

use App\Models\Order;

require_once __DIR__ . '/../config/database.php';

class OrderRepository {
  public function create($order) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, order_number, total_value, status)
      VALUES (:customer_id, :order_number, :total_value, :status)");
    $stmt->execute([
      ':customer_id' => $order->customer_id,
      ':order_number' => $order->order_number,
      ':total_value' => $order->total_value,
      ':status' => $order->status
    ]);
    $order->id = $pdo->lastInsertId();
    $pdo = null;
    return $order;
  }

  public function findById($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();
    $pdo = null;
    return $data ? new Order($data) : null;
  }

  public function updateStatus($id, $status) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmt->execute([
      ':status' => $status,
      ':id' => $id
    ]);
    $rows = $stmt->rowCount();
    $pdo = null;
    return $rows;
  }

  public function findAll() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT * FROM orders");
    $orders = $stmt->fetchAll();
    $pdo = null;
    return array_map(fn($data) => new Order($data), $orders);
  }

  public function getSummary() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT 
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) AS paid
      FROM orders");
    $summary = $stmt->fetch();
    $pdo = null;
    return $summary;
  }

}
