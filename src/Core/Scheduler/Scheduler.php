<?php

namespace Undertext\Microframework\Core\Scheduler;

/**
 * Class Scheduler
 *
 * @package Undertext\Dota2Service\Framework\Scheduler
 */
class Scheduler {

  /**
   * Scheduler tasks.
   *
   * @var \Undertext\Dota2Service\Framework\Scheduler\Task[]
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
