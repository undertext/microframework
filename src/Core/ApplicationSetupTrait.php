<?php

namespace Undertext\Microframework\Core;

use ARouter\Routing\HttpMessageConverter\HttpMessageConverterManager;
use ARouter\Routing\Resolver\CookieValueArgumentResolver;
use ARouter\Routing\Resolver\PathArgumentResolver;
use ARouter\Routing\Resolver\RequestArgumentResolver;
use ARouter\Routing\Resolver\RequestBodyArgumentResolver;
use ARouter\Routing\Resolver\RequestHeaderArgumentResolver;
use ARouter\Routing\Resolver\RequestParamArgumentResolver;
use ARouter\Routing\Resolver\Service\MethodArgumentsResolverService;
use ARouter\Routing\Resolver\SessionAttributeArgumentResolver;
use ARouter\Routing\Router;
use ARouter\Routing\Scanner\AnnotationRouteMappingsScanner;
use ARouter\Routing\Scanner\CachedRouteMappingsScanner;
use ARouter\Routing\Utility\PHPClassesDetector;
use function DI\factory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Psr\Container\ContainerInterface;
use Undertext\Dota2Service\Framework\Scheduler\SchedulerServicesProcessor;
use Undertext\Microframework\Core\Converter\JsonHttpMessageConverter;
use Undertext\Microservice\Core\Router\DIRouter;

trait ApplicationSetupTrait {

  /**
   * @var \Undertext\Microframework\Core\ServicesManager
   */
  private $servicesManager;

  /**
   * Use Doctrine ORM.
   *
   * @param array $connectionParams
   *   Database connection parameters.
   *
   * @return $this
   */
  public function useDoctrine(array $connectionParams) {
    $this->servicesManager->addProperty('doctrine.connectionParams', $connectionParams);
    $definition = factory(function (ContainerInterface $container) {
      $connectionParams = $container->get('doctrine.connectionParams');
      $entitiesDirectory = "src/Entity";
      $config = Setup::createAnnotationMetadataConfiguration([$entitiesDirectory], TRUE);
      $entityManager = EntityManager::create($connectionParams, $config);
      return $entityManager;
    });
    $this->servicesManager->addServiceDefinition(EntityManager::class, $definition);
    return $this;
  }

  /**
   * Use scheduler.
   *
   * @return $this
   *
   * @throws \ReflectionException
   */
  public function useScheduler() {
    $this->servicesManager->addServiceProcessor(SchedulerServicesProcessor::class);
    return $this;
  }

  /**
   * Use router library.
   *
   * @param $controllersDirectory
   *   Controllers directory.
   *
   * @return $this
   */
  public function useRouter($controllersDirectory) {
    $this->servicesManager->addProperty('router.controllersDirectory', $controllersDirectory);
    $definition = factory(function (ContainerInterface $container) {
      $controllersDirectory = $container->get('router.controllersDirectory');
      $argumentsResolverService = new MethodArgumentsResolverService();
      $argumentsResolverService->addArgumentResolvers([
        new RequestArgumentResolver(),
        new RequestParamArgumentResolver(),
        new PathArgumentResolver(),
        new RequestBodyArgumentResolver(),
        new CookieValueArgumentResolver(),
        new RequestHeaderArgumentResolver(),
        new SessionAttributeArgumentResolver(),
      ]);
      $converterManager = new HttpMessageConverterManager();
      $converterManager->addConverters([new JsonHttpMessageConverter()]);

      $scanner = new AnnotationRouteMappingsScanner($controllersDirectory, new AnnotationReader(), new PHPClassesDetector());
      $scanner = new CachedRouteMappingsScanner($scanner);

      $router = new DIRouter($scanner, $argumentsResolverService, $converterManager, $container);
      $router->setContainer($container);
      return $router;
    });

    $this->servicesManager->addServiceDefinition(Router::class, $definition);
    return $this;
  }
}
