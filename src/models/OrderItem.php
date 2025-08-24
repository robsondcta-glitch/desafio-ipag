<?php 

  namespace App\Models;

  class OrderItem {
    public $id;
    public $order_id;
    public $product_name;
    public $quantity;
    public $unit_value;
    
    public function __construct($data) {
      foreach ($data as $key => $value) {
        $this->$key = $value;
      }
    }
  }
