<?php

require_once __DIR__ . '/../../config/database.php';

try {
  $pdo = getPDO();

  $sql = "CREATE TABLE IF NOT EXISTS orders (
          id INT AUTO_INCREMENT PRIMARY KEY,
          customer_id INT NOT NULL,
          order_number VARCHAR(50) NOT NULL UNIQUE,
          total_value DECIMAL(10,2) NOT NULL,
          status ENUM('PENDING','WAITING_PAYMENT','PAID','PROCESSING','SHIPPED','DELIVERED','CANCELED') DEFAULT 'PENDING',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=INNODB";

  $pdo->exec($sql);
  echo "Tabela 'orders' criada.\n";
} catch (PDOException $ex) {
  echo "Erro ao criar a tabela de 'orders'.\n";
} finally {
  $pdo = null;
}

