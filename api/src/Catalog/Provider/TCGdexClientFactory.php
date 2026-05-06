<?php

declare(strict_types=1);

namespace App\Catalog\Provider;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\Psr18Client;
use TCGdex\TCGdex;

/**
 * Symfony service factory for TCGdex\TCGdex (see services.yaml). Wires
 * the SDK's static PSR slots (HTTP client, cache, factories) once on
 * instantiation, then `create()` returns a TCGdex instance the rest of
 * the app can have autowired straight into a constructor.
 */
final class TCGdexClientFactory
{
    public function __construct(
        ClientInterface $httpClient = new Psr18Client(),
        CacheInterface $cache = new Psr16Cache(new ArrayAdapter()),
        RequestFactoryInterface $requestFactory = new Psr17Factory(),
        ResponseFactoryInterface $responseFactory = new Psr17Factory(),
    ) {
        TCGdex::$client = $httpClient;
        TCGdex::$cache = $cache;
        TCGdex::$requestFactory = $requestFactory;
        TCGdex::$responseFactory = $responseFactory;
    }

    public function create(): TCGdex
    {
        return new TCGdex();
    }
}
