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
use function DI\create;
use function DI\factory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Psr7\ServerRequest;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Relay\Relay;
use Symfony\Component\Dotenv\Dotenv;
use Undertext\Microframework\Core\Converter\JsonHttpMessageConverter;
use Undertext\Microframework\Core\Router\DIRouter;
use Undertext\Microframework\Core\Scheduler\SchedulerServicesProcessor;

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
   * Setup environment variables.
   *
   * @param $directory
   *   Directory with .env file.
   *
   * @return \Undertext\Microframework\Core\ApplicationSetupTrait
   */
  public function setupEnvironment($directory) {
    $dotenv = new Dotenv();
    $dotenv->load($directory);
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
    $this->servicesManager->addProperty('router.cacheDirectory', $this->servicesManager->getApplicationRoot() . '/cache/cachedRouteMappings.cache');
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
      $scanner = new CachedRouteMappingsScanner($scanner, $container->get('router.cacheDirectory'));

      $router = new DIRouter($scanner, $argumentsResolverService, $converterManager, $container);
      $router->setContainer($container);
      return $router;
    });

    $this->servicesManager->addServiceDefinition(Router::class, $definition);
    return $this;
  }

  public function useLogger() {
    $definition = create(Logger::class)->constructor('microframework', [create(StreamHandler::class)->constructor('php://stderr')]);
    $this->servicesManager->addServiceDefinition(LoggerInterface::class, $definition);
    return $this;
  }

  public function handleHTTPRequest($middlewares) {
    try {
      $relay = new Relay($middlewares);
      $response = $relay->handle(ServerRequest::fromGlobals());
    } catch (\Exception $e) {
      echo $e->getMessage();
      exit;
    }
    foreach ($response->getHeaders() as $name => $header) {
      header($name . ':' . $response->getHeaderLine($name));
    }
    echo $response->getBody();
    exit;
  }

}
