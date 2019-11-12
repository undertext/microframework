<?php


namespace Undertext\Microframework\Core\Scheduler;


use function DI\factory;
use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Container\ContainerInterface;
use Undertext\Dota2Service\Framework\Core\ServicesProcessor;
use Undertext\Microframework\Core\Scheduler\Annotation\Scheduled;
use Undertext\Microframework\Core\ServicesManager;

class SchedulerServicesProcessor implements ServicesProcessor {

  /**
   * @var \Doctrine\Common\Annotations\AnnotationReader
   */
  private $annotationReader;

  /**
   * SchedulerServicesProcessor constructor.
   *
   * @param \Doctrine\Common\Annotations\AnnotationReader $annotationReader
   */
  public function __construct() {
    $this->annotationReader = new AnnotationReader();
  }

  /**
   * @param \Undertext\Microframework\Core\ServicesManager $servicesManager
   *
   * @throws \ReflectionException
   */
  public function process(ServicesManager $servicesManager) {
    foreach ($servicesManager->getDefinitions() as $service => $definition) {
      $class = new \ReflectionClass($service);
      foreach ($class->getMethods() as $method) {
        /** @var Scheduled $methodAnnotation */
        $methodAnnotation = $this->annotationReader->getMethodAnnotation($method, Scheduled::class);
        if ($methodAnnotation) {
          $tasks[] = [
            $methodAnnotation->cron,
            [$method->getDeclaringClass()->getName(), $method->getName()],
          ];
        }
      }
    }
    $servicesManager->addProperty('scheduler.tasks', $tasks);
    $definition = factory(function (ContainerInterface $container) {
      $tasks = $container->get('scheduler.tasks');
      $s = new Scheduler();
      foreach ($tasks as $task) {
        $m = $task[1];
        $t = new Task($task[0], function () use ($m, $container) {
          $container->get($m[0])->{$m[1]}();
        });
        $s->addTask($t->getCron(), $t->getCallable());
      }
      return $s;
    });
    $servicesManager->addServiceDefinition(Scheduler::class, $definition);
  }
}
