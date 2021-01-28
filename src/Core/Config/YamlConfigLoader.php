<?php

namespace Undertext\Microframework\Core\Config;

use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader {

  public function loadConfigFile($filename) {
    $yaml = Yaml::parseFile($filename, Yaml::PARSE_CUSTOM_TAGS);
    array_walk_recursive($yaml, function (&$item, $key) use ($filename) {
      if ($item instanceof TaggedValue) {
        switch ($item->getTag()) {
          case '!ENV':
            $envVariableDefaultValue = NULL;
            if (is_array($item->getValue())) {
              $envVariableName = $item->getValue()[0];
              $envVariableDefaultValue = $item->getValue()[1];
            }
            else {
              $envVariableName = $item->getValue();
            }
            $item = getenv($envVariableName);
            if (empty($item) && !empty($envVariableDefaultValue)) {
              $item = $envVariableDefaultValue;
            }
            break;
          default:
            throw new \Exception("Unsupported tag {$item->getTag()} is used in $filename.");
        }
      }
    });
  }

}
