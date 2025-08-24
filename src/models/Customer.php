<?php

namespace App\Models;

class Customer {
  public $id;
  public $name;
  public $document;
  public $email;
  public $phone;
  public $created_at;

  public function __construct($data) {
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
  }
}