<?php

namespace App\Controllers;

use App\Services\OrderService;

class OrderController {

  private $service;

  public function __construct() {
    $this->service = new OrderService();
  }

  public function create($request, $response) {
    $data = json_decode($request->getBody(), true);
    $customer_data = $data['customer'];
    $order_data = $data['order'];

    try {
      $result = $this->service->createOrder($customer_data, $order_data);
      $response->getBody()->write(json_encode($result));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }


  public function updateStatus($request, $response, $args) {
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

  public function list($request, $response) {
    // futuramente chamar OrderService para listar
    $orders = []; // placeholder
    $response->getBody()->write(json_encode($orders));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function summary($request, $response) {
    // futuramente chamar OrderService para gerar resumo estatístico
    $summary = [
      'total_orders' => 0,
      'pending' => 0,
      'paid' => 0
    ]; // placeholder
    $response->getBody()->write(json_encode($summary));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getById($request, $response, $args) {
    $order_id = $args['order_id'];
    try {
      $order = $this->service->getById($order_id); // precisa implementar no OrderService
      $response->getBody()->write(json_encode($order));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    return $response->withHeader('Content-Type', 'application/json');
  }
}
