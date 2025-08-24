<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\OrderService;
use App\Validators\OrderValidator;

class OrderController {

  private $service;

  public function __construct() {
    $this->service = new OrderService();
  }

  public function create(Request $request, Response $response): Response {
    // Lê o corpo da requisição como JSON
    $data = json_decode($request->getBody()->getContents(), true);

    if (!$data) {
      $response->getBody()->write(json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
      ], JSON_UNESCAPED_UNICODE));

      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Validação
    $errors = OrderValidator::validate($data);

    if (!empty($errors)) {
      $response->getBody()->write(json_encode([
        "status" => "error",
        "errors" => $errors
      ], JSON_UNESCAPED_UNICODE));

      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
      $result = $this->service->createOrder($data);
      $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
      return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        "status" => "error",
        "message" => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE));

      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
  }

  public function updateStatus(Request $request, Response $response, $args): Response {
    $order_id = $args['order_id'];
    $data = json_decode($request->getBody(), true);
    $new_status = $data['status'];

    try {
      $order = $this->service->changeStatus($order_id, $new_status);
      $response->getBody()->write(json_encode($order));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function list(Request $request, Response $response): Response {
    $params = $request->getQueryParams();

    $status = $params['status'] ?? null;
    $customer_id = $params['customer_id'] ?? null;
    $start_date = $params['start_date'] ?? null;
    $end_date = $params['end_date'] ?? null;

    $orders = $this->service->listOrders($status, $customer_id, $start_date, $end_date);

    $response->getBody()->write(json_encode($orders));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function summary(Request $request, Response $response): Response {
    $summary = $this->service->getSummary();
    $response->getBody()->write(json_encode($summary, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getById(Request $request, Response $response, $args): Response {
    $order_id = $args['order_id'];
    try {
      $order = $this->service->getByOrderNumber($order_id);
      $response->getBody()->write(json_encode($order));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    return $response->withHeader('Content-Type', 'application/json');
  }
}
