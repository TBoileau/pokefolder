<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class HealthcheckControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testReturnsOkWhenAllServicesAreUp(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/health');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(
            ['db' => 'ok', 'amqp' => 'ok'],
            json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
