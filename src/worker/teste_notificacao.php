<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Autoload do Composer
require_once __DIR__ . '/../../vendor/autoload.php'; // Ajuste o caminho conforme a posição do vendor

use App\Services\NotificationLogService;

$created_at = date('Y-m-d H:i:s');

$service = new NotificationLogService();
$log = $service->createNotificationLog([
    'order_id'   => 10,
    'old_status' => 'WAITING_PAYMENT',
    'new_status' => 'PAID',
    'message'    => 'teste',
    'created_at' => $created_at,
]);

echo "Notification log criado com ID: " . $log->id . PHP_EOL;
