<?php

namespace Undertext\Microservice\Core\Router;

use ARouter\Routing\HttpMessageConverter\HttpMessageConverterManager;
use ARouter\Routing\Resolver\Service\MethodArgumentsResolverService;
use ARouter\Routing\Router;
use ARouter\Routing\Scanner\RouteMappingsScannerInterface;
use Psr\Container\ContainerInterface;

/**
 * Container-aware router.
 *
 * @package Undertext\Microservice\Core\Router
 */
class DIRouter extends Router {

  /**
   * @var \Psr\Container\ContainerInterface
   */
  private $container;

  /**
   * DIRouter constructor.
   *
   * @param \ARouter\Routing\Scanner\RouteMappingsScannerInterface $scanner
   * @param \ARouter\Routing\Resolver\Service\MethodArgumentsResolverService $argumentsResolverService
   * @param \ARouter\Routing\HttpMessageConverter\HttpMessageConverterManager $converterManager
   * @param $container
   */
  public function __construct(RouteMappingsScannerInterface $scanner, MethodArgumentsResolverService $argumentsResolverService, HttpMessageConverterManager $converterManager, $container) {
    parent::__construct($scanner, $argumentsResolverService, $converterManager);
    $this->container = $container;
  }


  protected function getControllerInstance(string $controllerName) {
    return $this->container->get($controllerName);
  }

  /**
   * @return \Psr\Container\ContainerInterface
   */
  public function getContainer(): ContainerInterface {
    return $this->container;
  }

  /**
   * @param \Psr\Container\ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container): void {
    $this->container = $container;
  }

}
