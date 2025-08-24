<?php

namespace App\Models;

class Order {
  public $id;
  public $customer_id;
  public $order_number;
  public $total_value;
  public $status;
  public $created_at;
  public $updated_at;

  public function __construct($data) {
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
  }
}
