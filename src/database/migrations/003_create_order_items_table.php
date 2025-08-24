<?php
require_once __DIR__ . '/../../config/database.php';

try {
  $pdo = getPDO();

  $sql = "CREATE TABLE IF NOT EXISTS order_items (
          id INT AUTO_INCREMENT PRIMARY KEY,
          order_id INT NOT NULL,
          product_name VARCHAR(150) NOT NULL,
          quantity INT NOT NULL,
          unit_value DECIMAL(10,2) NOT NULL,
          FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=INNODB";

  $pdo->exec($sql);
  echo "Tabela 'order_items' criada.\n";
} catch (PDOException $ex) {
  echo "Erro ao criar a tabela de 'order_items'.\n";
} finally {
  $pdo = null;
}

