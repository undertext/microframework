<?php

namespace Undertext\Microframework\Core\Converter;

use ARouter\Routing\HttpMessageConverter\HttpMessageConverterInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonHttpMessageConverter implements HttpMessageConverterInterface {

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  private $serializer;

  public function __construct() {
    $encoder = new JsonEncoder();
    $this->serializer = new Serializer([new DateTimeNormalizer('Y-m-d H:i:s'), new GetSetMethodNormalizer()], [$encoder]);
  }

  public function toResponse($object): ResponseInterface {
    return new Response(200, [], $this->serializer->serialize($object, 'json'));
  }

  /**
   * Get response formats supported by this converter.
   *
   * @return string[]
   */
  public function getFormats(): array {
    return ['text/html', 'application/json'];
  }
}
