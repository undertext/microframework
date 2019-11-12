<?php

namespace Undertext\Microframework\Core\Scheduler;

/**
 * Class Scheduler.
 */
class Scheduler {

  /**
   * Scheduler tasks.
   *
   * @var \Undertext\Microframework\Core\Scheduler\Task[]
   */
  private $tasks;

  public function addTask(string $cron, callable $callable) {
    $task = new Task($cron, $callable);
    $this->tasks[] = $task;
  }

  public function run() {
    foreach ($this->tasks as $task) {
      $cron = \Cron\CronExpression::factory($task->getCron());
      if ($cron->isDue()) {
        $task->getCallable()();
      }
    }
  }

}
