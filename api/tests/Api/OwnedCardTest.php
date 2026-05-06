<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\Condition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class OwnedCardTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetCollectionReturnsEmptyHydraCollectionInitially(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/owned_cards');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            '@type' => 'Collection',
            'totalItems' => 0,
        ]);
    }

    public function testPostCreatesOneOwnedCard(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $card = new Card(
            setId: 'base1',
            numberInSet: '4',
            variant: 'holo',
            language: 'en',
            name: 'Charizard',
            rarity: 'Rare Holo',
        );
        $em->persist($card);
        $em->flush();

        $client->request('POST', '/api/owned_cards', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'card' => '/api/cards/'.$card->getId()->toRfc4122(),
                'condition' => Condition::NearMint->value,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJsonContains([
            '@type' => 'OwnedCard',
            'condition' => 'NM',
        ]);
    }

    public function testPatchUpdatesCondition(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $card = new Card(
            setId: 'base1',
            numberInSet: '4',
            variant: 'holo',
            language: 'en',
            name: 'Charizard',
            rarity: 'Rare Holo',
        );
        $ownedCard = new OwnedCard(card: $card, condition: Condition::NearMint);
        $em->persist($card);
        $em->persist($ownedCard);
        $em->flush();

        $client->request('PATCH', '/api/owned_cards/'.$ownedCard->getId()->toRfc4122(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['condition' => Condition::LightPlayed->value],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['condition' => 'LP']);
    }

    public function testDeleteRemovesTheRow(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $card = new Card(
            setId: 'base1',
            numberInSet: '4',
            variant: 'holo',
            language: 'en',
            name: 'Charizard',
            rarity: 'Rare Holo',
        );
        $ownedCard = new OwnedCard(card: $card, condition: Condition::NearMint);
        $em->persist($card);
        $em->persist($ownedCard);
        $em->flush();

        $client->request('DELETE', '/api/owned_cards/'.$ownedCard->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testGetItemReturns404WhenOwnedCardDoesNotExist(): void
    {
        $client = self::createClient();
        $randomId = Uuid::v7()->toRfc4122();
        $client->request('GET', '/api/owned_cards/'.$randomId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
