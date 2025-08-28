<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Models\Customer;

class CustomerService {

  private $repo;

  public function __construct() {
    $this->repo = new CustomerRepository();
  }

  public function createCustomer($data) {
    $customer = new Customer($data);
    return $this->repo->create($customer);
  }

  public function verifyCustomer($id) {
    $customer = $this->repo->findById($id);
    if (empty($customer)) {
      return false;
    }
    return true;
  }

  public function getById($id) {
    $customer = $this->repo->findById($id);
    if (empty($customer)) {
      throw new \Exception("Customer not found");
    }
    return $customer;
  }

  public function getByOrderId($order_id) {
    $customer = $this->repo->findByOrderId($order_id);
    if (empty($customer)) {
      throw new \Exception("Customer not found");
    }
    return $customer;
  }

  public function getByOrderNumber($order_id) {
    $customer = $this->repo->findByOrderNumber($order_id);
    if (empty($customer)) {
      throw new \Exception("Customer not found");
    }
    return $customer;
  }
}