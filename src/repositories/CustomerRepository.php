<?php

namespace App\Repositories;

use App\Models\Customer;

require_once __DIR__ . '/../config/database.php';

class CustomerRepository {
  public function create($customer) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO customers (name, document, email, phone)
      VALUES (:name, :document, :email, :phone)");
    $stmt->execute([
      ':name' => $customer->name,
      ':document' => $customer->document,
      ':email' => $customer->email,
      ':phone' => $customer->phone,
    ]);
    $customer->id = $pdo->lastInsertId();
    $pdo = null;
    return $customer;
  }

  public function findById($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch();
    $pdo = null;
    return $data ? new Customer($data) : null;
  }
}