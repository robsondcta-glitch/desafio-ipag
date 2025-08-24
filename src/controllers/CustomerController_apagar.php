<?php

namespace App\Controllers;

use \App\Services\CustomerService;

class CustomerController {
  private $service;

  public function __construct() {
    $this->service = new CustomerService();
  }

  public function create($request, $response) {
    $data = json_decode($request->getBody(), true);

    try {
      $customer = $this->service->createCustomer($data);
      $response->getBody()->write(json_encode($customer));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->whiteStatus(400)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function getById($request, $response, $args) {
    try {
      $customer = $this->service->getById($args['id']);
      $response->getBody()->write(json_encode($customer));
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
      return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
  }
}