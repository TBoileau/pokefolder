<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\PokemonSet;
use App\Entity\Rarity;
use App\Entity\Serie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class CatalogResourcesTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetSeriesReturnsListWithTranslations(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $serie = new Serie('base');
        $serie->upsertTranslation('en', 'Base');
        $serie->upsertTranslation('fr', 'Base FR');

        $em->persist($serie);
        $em->flush();

        $client->request('GET', '/api/series');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testGetSetsFilterableBySerie(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $serieA = new Serie('a');
        $serieA->upsertTranslation('en', 'A');

        $em->persist($serieA);
        $serieB = new Serie('b');
        $serieB->upsertTranslation('en', 'B');

        $em->persist($serieB);

        $setA1 = new PokemonSet('a1', $serieA);
        $setA1->upsertTranslation('en', 'A1');

        $em->persist($setA1);
        $setB1 = new PokemonSet('b1', $serieB);
        $setB1->upsertTranslation('en', 'B1');

        $em->persist($setB1);
        $em->flush();

        $client->request('GET', '/api/pokemon_sets?serie=/api/series/a');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testGetRarities(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $rarity = new Rarity('rare-holo');
        $rarity->upsertTranslation('en', 'Rare Holo');

        $em->persist($rarity);
        $em->flush();

        $client->request('GET', '/api/rarities');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testVariantsEndpoint(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/variants');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            ['code' => 'normal', 'label' => 'Normal'],
        ]);
    }

    public function testLanguagesEndpoint(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/languages');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            ['code' => 'en'],
        ]);
    }
}
