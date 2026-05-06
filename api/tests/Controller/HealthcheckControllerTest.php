<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use const JSON_THROW_ON_ERROR;

final class HealthcheckControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testReturnsOkWhenAllServicesAreUp(): void
    {
        $client = self::createClient();

        $response = $client->request('GET', '/health');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(
            ['db' => 'ok', 'amqp' => 'ok'],
            json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
