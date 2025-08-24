<?php
$files = glob(__DIR__ . "/migrations/*.php");

foreach ($files as $file) {
  echo "▶ Executando: " . basename($file) . "\n";
  require $file;
}
