<?php 

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\NotificationLogService;

class NotificationLogController {
  private $service;

  public function __construct() {
    $this->service = new NotificationLogService();
  }

  public function create(Request $request, Response $response): Response {
    $data = json_decode($request->getBody(), true);
    $notificationLogData = $data['notificationlog'];

    try {
      $result = $this->service->createNotificationLog($data);
      $response->getBody()->write(json_encode($notificationLogData));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getById(Request $request, Response $response, $args): Response {
    try {
      $customer = $this->service->getByOrderId($args['id']);
      $response->getBody()->write(json_encode($customer));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }
}