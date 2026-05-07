<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Support\CardFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class RarityCardScopeExtensionTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testRaritiesScopedToSetIncludeOnlyRaritiesUsedInThatSet(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'sv01', serieId: 'sv', numberInSet: '1', name: 'A', rarityCode: 'common');
        CardFixtureFactory::create($em, setId: 'sv01', serieId: 'sv', numberInSet: '2', name: 'B', rarityCode: 'rare');
        CardFixtureFactory::create($em, setId: 'sv02', serieId: 'sv', numberInSet: '1', name: 'C', rarityCode: 'illustration-rare');
        $em->flush();

        $client->request('GET', '/api/rarities?pokemonSet=/api/pokemon_sets/sv01&order[code]=asc');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 2]);
        self::assertJsonContains([
            'member' => [
                ['@type' => 'Rarity', 'code' => 'common'],
                ['@type' => 'Rarity', 'code' => 'rare'],
            ],
        ]);
    }

    public function testRaritiesScopedToSerieIncludeRaritiesAcrossAllItsSets(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'sv01', serieId: 'sv', numberInSet: '1', name: 'A', rarityCode: 'common');
        CardFixtureFactory::create($em, setId: 'sv02', serieId: 'sv', numberInSet: '1', name: 'B', rarityCode: 'rare');
        CardFixtureFactory::create($em, setId: 'base1', serieId: 'base', numberInSet: '1', name: 'C', rarityCode: 'holo-rare');
        $em->flush();

        $client->request('GET', '/api/rarities?serie=/api/series/sv&order[code]=asc');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 2]);
        self::assertJsonContains([
            'member' => [
                ['@type' => 'Rarity', 'code' => 'common'],
                ['@type' => 'Rarity', 'code' => 'rare'],
            ],
        ]);
    }

    public function testRaritiesUnscopedReturnsAll(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'sv01', serieId: 'sv', numberInSet: '1', name: 'A', rarityCode: 'common');
        CardFixtureFactory::create($em, setId: 'base1', serieId: 'base', numberInSet: '1', name: 'B', rarityCode: 'holo-rare');
        $em->flush();

        $client->request('GET', '/api/rarities');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 2]);
    }

    public function testSetScopeWinsOverSerieWhenBothProvided(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'sv01', serieId: 'sv', numberInSet: '1', name: 'A', rarityCode: 'common');
        CardFixtureFactory::create($em, setId: 'sv02', serieId: 'sv', numberInSet: '1', name: 'B', rarityCode: 'rare');
        $em->flush();

        $client->request(
            'GET',
            '/api/rarities?serie=/api/series/sv&pokemonSet=/api/pokemon_sets/sv01',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
        self::assertJsonContains([
            'member' => [
                ['@type' => 'Rarity', 'code' => 'common'],
            ],
        ]);
    }
}
