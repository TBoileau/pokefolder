<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class CardTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetCollectionReturnsEmptyHydraCollectionInitially(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/cards');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            '@context' => '/api/contexts/Card',
            '@id' => '/api/cards',
            '@type' => 'Collection',
            'totalItems' => 0,
            'member' => [],
        ]);
    }

    public function testGetItemReturns404WhenCardDoesNotExist(): void
    {
        $client = self::createClient();
        $randomId = Uuid::v7()->toRfc4122();

        $client->request('GET', '/api/cards/'.$randomId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
