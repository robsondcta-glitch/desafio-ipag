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

  public function findByOrderNumber($order_number) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number");
    $stmt->execute([':order_number' => $order_number]);
    $data = $stmt->fetch();
    $pdo = null;
    return $data ? new Order($data) : null;
  }

  public function updateStatusByOrderNumber($order_number, $status) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE order_number = :order_number");
    $stmt->execute([
      ':status' => $status,
      ':order_number' => $order_number
    ]);
    $rows = $stmt->rowCount();
    $pdo = null;
    return $rows;
  }

  public function findAll($status = null, $customer_id = null, $start_date = null, $end_date = null) {
    $pdo = getPDO();
    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if ($status) {
      $query .= " AND status = :status";
      $params[':status'] = $status;
    }

    if ($customer_id) {
      $query .= " AND customer_id = :customer_id";
      $params[':customer_id'] = $customer_id;
    }

    if ($start_date) {
      $query .= " AND created_at >= :start_date";
      $params[':start_date'] = $start_date . " 00:00:00";
    }

    if ($end_date) {
      $query .= " AND created_at <= :end_date";
      $params[':end_date'] = $end_date . " 23:59:59";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $orders = $stmt->fetchAll();
    $pdo = null;
    return array_map(fn($data) => new Order($data), $orders);
  }

  public function getSummary() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT
        COUNT(*) AS total_orders,
        SUM(total_value) AS total_revenue,
        ROUND(AVG(total_value), 2) AS avg_ticket,
        MIN(total_value) AS min_order_value,
        MAX(total_value) AS max_order_value,
        COUNT(DISTINCT customer_id) AS total_customers,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) AS paid_orders,
        SUM(CASE WHEN status = 'SHIPPED' THEN 1 ELSE 0 END) AS shipped_orders,
        SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS delivered_orders,
        SUM(CASE WHEN status = 'CANCELED' THEN 1 ELSE 0 END) AS canceled_orders
      FROM orders");
    $summary = $stmt->fetch();
    $pdo = null;
    return $summary;
  }

  public function verifyOrderNumber($order_number) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number");
    $stmt->execute([':order_number' => $order_number]);
    $order = $stmt->fetch();
    $pdo = null;
    return empty($order); // true se não encontrou (número é único)
  }

}
