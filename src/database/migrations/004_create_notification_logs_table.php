<?php

require_once __DIR__ . '/../../config/database.php';

try {
  $pdo = getPDO();

  $sql = "CREATE TABLE IF NOT EXISTS notification_logs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          order_id INT NOT NULL,
          old_status VARCHAR(20) NOT NULL,
          new_status VARCHAR(20) NOT NULL,
          message TEXT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=INNODB";

  $pdo->exec($sql);
  echo "Tabela 'notification_logs' criada.\n";
} catch (PDOException $ex) {
  echo "Erro ao criar a tabela de 'notification_logs'.\n";
} finally {
  $pdo = null;
}

