<?php

namespace Undertext\Microframework\Core;

use ARouter\Routing\Utility\PHPClassesDetector;
use function DI\autowire;
use DI\ContainerBuilder;
use function DI\get;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use Undertext\Microframework\Core\Annotation\Service;

class ServicesManager {

  /**
   * Service classes.
   *
   * @var array
   */
  private $serviceDefinitions;

  /**
   * Processor classes.
   *
   * @var ReflectionClass[]
   */
  private $processorClasses;

  /**
   * Container builder.
   *
   * @var ContainerBuilder
   */
  private $containerBuilder;

  /**
   * @var \Doctrine\Common\Annotations\AnnotationReader
   */
  private $annotationReader;

  /**
   * @var \ARouter\Routing\Utility\PHPClassesDetector
   */
  private $phpClassesDetector;

  /**
   * ServicesManager constructor.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException
   * @throws \ReflectionException
   */
  public function __construct() {
    $this->annotationReader = new AnnotationReader();
    $this->phpClassesDetector = new PHPClassesDetector();
    $this->containerBuilder = new ContainerBuilder();
    $this->serviceDefinitions = $this->detectServiceClasses();
    $this->processorClasses = [];
  }

  /**
   * Add a service definition.
   *
   * @param $class
   *   Service class.
   * @param $definition
   *   Service definition
   */
  public function addServiceDefinition($class, $definition) {
    $this->serviceDefinitions[$class] = $definition;
  }

  /**
   * Add container property.
   *
   * @param string $name
   *   Property name.
   * @param $value
   *   Property value.
   */
  public function addProperty(string $name, $value) {
    $this->containerBuilder->addDefinitions([$name => $value]);
  }

  /**
   * Get all service definitions.
   *
   * @return array|\ReflectionClass[]
   *   All service definitions.
   */
  public function getDefinitions() {
    return $this->serviceDefinitions;
  }

  /**
   * Add service processor.
   *
   * @param string $servicesProcessor
   *
   * @throws \ReflectionException
   */
  public function addServiceProcessor($servicesProcessor) {
    $this->processorClasses[] = $servicesProcessor;
  }

  /**
   * Init container builder.
   *
   * @throws \ReflectionException
   */
  private function initContainerBuilder() {
    $aliases = [];
    $this->containerBuilder->useAnnotations(TRUE);
    $this->containerBuilder->enableCompilation(__DIR__ . '/var/cache');
    foreach ($this->serviceDefinitions as $serviceClass => $definition) {
      $definitions[$serviceClass] = $definition;
      $serviceClassReflection = new ReflectionClass($serviceClass);
      $interfaces = $serviceClassReflection->getInterfaceNames();
      if (count($interfaces) == 1) {
        $interface = reset($interfaces);
        $aliases[$interface] = get($serviceClass);
      }
    }

    foreach ($this->processorClasses as $processorClass) {
      /** @var \Undertext\Microframework\Core\ServicesProcessor $serviceProcessor */
      $serviceProcessor = new $processorClass();
      $serviceProcessor->process($this);
    }
    $this->containerBuilder->addDefinitions($this->serviceDefinitions + $aliases);

  }

  public function buildContainer() {
    $this->initContainerBuilder();
    $container = $this->containerBuilder->build();
    return $container;
  }

  /**
   * Detect Service classes in codebase.
   *
   * @return \ReflectionClass[]
   *   Service classes.
   * @throws \ReflectionException
   */
  private function detectServiceClasses() {
    $serviceClasses = [];
    $detectedClasses = $this->phpClassesDetector->detect(__DIR__ . '/../../');
    foreach ($detectedClasses as $class) {
      try {
        $reflectionClass = new ReflectionClass($class);
        $serviceAnnotation = $this->annotationReader->getClassAnnotation($reflectionClass, Service::class);
        if ($serviceAnnotation) {
          $serviceClasses[$class] = autowire($class);
        }
      } catch (\Exception $e) {

      }
    }
    return $serviceClasses;
  }

}
