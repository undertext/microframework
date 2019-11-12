<?php

namespace Undertext\Dota2Service\Framework\Core;

use Undertext\Microframework\Core\ServicesManager;
use Undertext\Microservice\Core;

interface ServicesProcessor {

  public function process(ServicesManager $servicesManager);

}
