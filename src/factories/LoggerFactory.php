<?php

namespace App\Factories;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class LoggerFactory
{
  public static function create(string $service_name): Logger {
    $logger = new Logger($service_name);

    $stream = new StreamHandler('php://stdout', Logger::DEBUG);
    $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, JsonFormatter::BATCH_MODE_NEWLINES);
    $stream->setFormatter($formatter);

    $logger->pushHandler($stream);

    return $logger;
  }
}