<?php 

namespace App\Services;

use App\Repositories\NotificationLogRepository;
use App\Models\NotificationLog;

use App\Services\CustomerService;
use App\Services\OrderService;

class NotificationLogService {
  private $repo;

  public function __construct() {
    $this->repo = new NotificationLogRepository();
  }

  public function createNotificationLog($data) {
    $order_service = new OrderService();
    $order = $order_service->getByOrderNumber($data['order_id']);

    $valid_next_status = [
      'PENDING' => [ 'message' => 'Pedido criado, aguardando pagamento', ],
      'WAITING_PAYMENT' => [ 'message' => 'Aguardando confirmação de pagamento', ],
      'PAID' => [ 'message' => 'Pagamento confirmado', ],
      'PROCESSING' => [ 'message' => 'Pedido em processamento', ],
      'SHIPPED' => [ 'message' => 'Pedido enviado', ],
      'DELIVERED' => [ 'message' => 'Pedido entregue ao cliente', ],
      'CANCELED' => [ 'message' => 'Pedido cancelado', ]
    ];

    $notification_log = new NotificationLog([
      'order_id' => $order->id,
      'old_status' => $data['old_status'],
      'new_status' => $data['new_status'],
      'message' => $valid_next_status[$data['new_status']]['message'],
      'created_at' => date('Y-m-d H:i:s')
    ]);

    return $this->repo->create($notification_log);
  }

  public function getByOrderId($order_id) {
    $notifications_log = $this->repo->findByOrderId($order_id);
    if (empty($notifications_log)) {
      throw new \Exception("Notification Log not found");
    }
    return $notifications_log;
  }

  public function getLastLogByOrderId($order_id) {
    $notifications_log = $this->repo->findLastLogByOrderId($order_id);
    if (empty($notifications_log)) {
      throw new \Exception("Notification Log not found");
    }
    return $notifications_log;
  }
}