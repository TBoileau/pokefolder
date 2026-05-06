<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class BinderTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetCollectionReturnsEmptyHydraCollectionInitially(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/binders');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            '@type' => 'Collection',
            'totalItems' => 0,
        ]);
    }

    public function testPostCreatesBinderAndExposesDerivedCapacity(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/binders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Master Set Base',
                'pageCount' => 20,
                'cols' => 3,
                'rows' => 3,
                'doubleSided' => true,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJsonContains([
            '@type' => 'Binder',
            'name' => 'Master Set Base',
            'capacity' => 360, // 20 × 3 × 3 × 2
        ]);
    }

    public function testValidationRejectsZeroPageCount(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/binders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Invalid',
                'pageCount' => 0,
                'cols' => 3,
                'rows' => 3,
                'doubleSided' => true,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPatchUpdatesName(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder(
            name: 'Old name',
            pageCount: 10,
            cols: 3,
            rows: 3,
        );
        $em->persist($binder);
        $em->flush();

        $client->request('PATCH', '/api/binders/'.$binder->getId()->toRfc4122(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Renamed'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['name' => 'Renamed']);
    }

    public function testDeleteRemovesTheBinder(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder(
            name: 'Throwaway',
            pageCount: 1,
            cols: 1,
            rows: 1,
        );
        $em->persist($binder);
        $em->flush();

        $client->request('DELETE', '/api/binders/'.$binder->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
