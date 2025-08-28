<?php 

namespace App\Validators;

class OrderValidator {
  public static function validate(array $data): array {
    $errors = [];

    /* Validação das informações enviadas do Customer */
    if (empty($data['customer']['name'])) {
      $errors[] = "O nome do cliente é obrigatório.";
    }

    if (empty($data['customer']['email']) || !filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = "E-mail é inválido.";
    }

    if (empty($data['customer']['document'])) {
      $errors[] = "O documento do cliente é obrigatório.";
    }

    $order_items = $data['order']['items'];
    /* Validação dos items do pedido */
    if (!isset($order_items) || !is_array($order_items) || count($order_items) == 0) {
      $errors[] = "O pedido precisa conter ao menos 1 item.";
    } else {
      foreach ($order_items as $i => $item) {
        if (empty($item['product_name'])) {
          $errors[] = "O produto $i precisa ser informado o product_name";
        }

        if (empty($item['quantity']) || $item['quantity'] <= 0) {
          $errors[] = "O produto $i precisa de uma quantidade válida";
        }

        if (!isset($item['unit_value']) || $item['unit_value'] <= 0) {
          $errors[] = "O produto #{$i} precisa de um preço unitário válido";
        }
      }
    }

    return $errors;
  }
}