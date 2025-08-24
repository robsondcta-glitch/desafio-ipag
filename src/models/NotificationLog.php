<?php 

  namespace App\Models;

  class NotificationLog {
    public $id;
    public $order_id;
    public $old_status;
    public $new_status;
    public $message;
    public $create_at;

    public function __construct($data) {
      foreach ($data as $key => $value) {
        $this->$key = $value;
      }
    }
  }