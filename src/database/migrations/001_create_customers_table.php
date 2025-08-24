<?php

require_once __DIR__ . '/../../config/database.php';

try {
  $pdo = getPDO();

  $sql = "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            document VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=INNODB";

  $pdo->exec($sql);
  echo "Tabela 'customers' criada.\n";
} catch (PDOException $ex) {
  echo "Erro ao criar a tabela de 'customers'.\n";
} finally {
  $pdo = null;
}

