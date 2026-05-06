<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\OwnedCard;
use App\Enum\Condition;
use App\Tests\Support\CardFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class SearchExtensionTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testCardsSearchByName(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'base1', numberInSet: '1', variant: 'normal', language: 'en', name: 'Charizard');
        CardFixtureFactory::create($em, setId: 'base1', numberInSet: '2', variant: 'normal', language: 'en', name: 'Bulbasaur');
        $em->flush();

        $client->request('GET', '/api/cards?search=charizard');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testCardsSearchByNumberInSet(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'base1', numberInSet: '042', variant: 'normal', language: 'en', name: 'Foo');
        CardFixtureFactory::create($em, setId: 'base1', numberInSet: '003', variant: 'normal', language: 'en', name: 'Bar');
        $em->flush();

        $client->request('GET', '/api/cards?search=042');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testCardsSearchBySetCode(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        CardFixtureFactory::create($em, setId: 'sv01', numberInSet: '5', variant: 'normal', language: 'en', name: 'Foo');
        CardFixtureFactory::create($em, setId: 'base1', numberInSet: '5', variant: 'normal', language: 'en', name: 'Foo');
        $em->flush();

        $client->request('GET', '/api/cards?search=sv01');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testOwnedCardsSearchByCardName(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $charizard = CardFixtureFactory::create($em, setId: 'base1', numberInSet: '1', variant: 'normal', language: 'en', name: 'Charizard');
        $bulbasaur = CardFixtureFactory::create($em, setId: 'base1', numberInSet: '2', variant: 'normal', language: 'en', name: 'Bulbasaur');
        $em->persist(new OwnedCard($charizard, Condition::NearMint));
        $em->persist(new OwnedCard($bulbasaur, Condition::NearMint));
        $em->flush();

        $client->request('GET', '/api/owned_cards?search=bulb');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
    }
}
