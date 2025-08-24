<?php 

namespace App\Services;

use App\Repositories\NotificationLogRepository;
use App\Models\NotificationLog;

class NotificationLogService {
  private $repo;

  public function __construct() {
    $this->repo = new NotificationLogRepository();
  }

  public function createNotificationLog($data) {
    $notification_log = new NotificationLog($data);
    return $this->repo->create($notification_log);
  }

  public function getByOrderId($order_id) {
    $notifications_log = $this->repo->findByOrderId($order_id);
    if (!$notifications_log)
      throw new \Exception("Notification Log not found");
    return $notifications_log;
  }

  public function getLastLogByOrderId($order_id) {
    $notifications_log = $this->repo->findLastLogByOrderId($order_id);
    if (!$notifications_log)
      throw new \Exception("Notification Log not found");
    return $notifications_log;
  }
}