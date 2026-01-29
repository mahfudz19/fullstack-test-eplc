<?php

namespace App\Core\Queue;

use App\Core\Database\DatabaseManager;
use App\Core\Queue\RedisQueue;
use App\Core\Queue\QueueInterface;

class JobDispatcher
{
  private QueueInterface $queue;

  public function __construct(DatabaseManager $db)
  {
    // Di masa depan, ini bisa diambil dari Config (misal: 'sync' atau 'redis')
    // Untuk sekarang, kita hardcode ke RedisQueue agar kompatibel dengan yang ada.
    $this->queue = new RedisQueue($db);
  }

  public function dispatch(string $jobClass, array $data = [], string $queue = 'default'): void
  {
    try {
      $this->queue->push($jobClass, $data, $queue);
    } catch (\Throwable $e) {
      $payloadJson = json_encode(['job' => $jobClass, 'data' => $data]);
      $message = "[JobDispatcher] ERROR DISPATCHING: Job '{$jobClass}' to queue '{$queue}'. Error: " . $e->getMessage() . ". Payload: " . substr($payloadJson, 0, 200) . "...\nException Trace:\n" . $e->getTraceAsString();
      error_log($message);
      throw $e;
    }
  }
}
