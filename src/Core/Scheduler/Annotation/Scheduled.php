<?php

namespace Undertext\Microframework\Core\Scheduler\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Scheduled {

  /**
   * Cron expression.
   *
   * @Required
   * @var string
   */
  public $cron;

}
