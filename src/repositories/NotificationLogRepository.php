<?php 

namespace App\Repositories;

use App\Models\NotificationLog;

require_once __DIR__ . '/../config/database.php';

class NotificationLogRepository {
  public function create($notification_log) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO notification_logs (order_id, old_status, new_status, message) 
      VALUES (:order_id, :old_status, :new_status, :message)");
    $stmt->execute([
      ':order_id' => $notification_log->order_id,
      ':old_status' => $notification_log->old_status,
      ':new_status' => $notification_log->new_status,
      ':message' => $notification_log->message,
    ]);
    $notification_log->id = $pdo->lastInsertId();
    $pdo = null;
    return $notification_log;
  }

  public function findByOrderId($order_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM notification_logs WHERE order_id = :order_id");
    $stmt->execute([':order_id' => $order_id]);
    $notifications_log = $stmt->fetchAll();
    $pdo = null;
    return array_map(fn($data) => new NotificationLog($data), $notifications_log);
  }

  public function findLastLogByOrderId($order_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM notification_logs 
      WHERE order_id = :order_id 
      ORDER BY create_at DESC 
      LIMIT 1");
    $stmt->execute([':order_id' => $order_id]);
    $notification_log = $stmt->fetch();
    $pdo = null;
    return new NotificationLog($notification_log);
  }
}