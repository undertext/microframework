<?php

namespace Undertext\Microframework\Core\Scheduler;

/**
 * Class Task
 */
class Task {

  private $cron;

  private $callable;

  /**
   * Task constructor.
   *
   * @param $cron
   * @param $callable
   */
  public function __construct($cron, $callable) {
    $this->cron = $cron;
    $this->callable = $callable;
  }

  /**
   * @return mixed
   */
  public function getCron() {
    return $this->cron;
  }

  /**
   * @return mixed
   */
  public function getCallable() {
    return $this->callable;
  }


}
