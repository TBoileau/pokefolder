<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Enum\Condition;
use App\Service\Binder\BinderSlotPosition;
use App\Tests\Support\CardFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class OwnedCardPlacedFilterTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testPlacedFalseReturnsOwnlyOwnedCardsThatAreInNoSlot(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder('Test', 5, 3, 3);
        $em->persist($binder);

        $card = CardFixtureFactory::create($em, setId: 'base1', numberInSet: '1', variant: 'normal', language: 'en', name: 'Card 1', rarityCode: 'common');
        $em->persist($card);

        $placedOwnedCard = new OwnedCard($card, Condition::NearMint);
        $em->persist($placedOwnedCard);
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $placedOwnedCard,
        ));

        $freeOwnedCard = new OwnedCard($card, Condition::Mint);
        $em->persist($freeOwnedCard);

        $em->flush();

        $client->request('GET', '/api/owned_cards?placed=false');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
        self::assertJsonContains([
            'member' => [
                ['@type' => 'OwnedCard', 'id' => $freeOwnedCard->getId()->toRfc4122()],
            ],
        ]);
    }

    public function testPlacedFalseEmbedsCardImageUrlInResponse(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $card = CardFixtureFactory::create($em, setId: 'base1', numberInSet: '7', variant: 'normal', language: 'en', name: 'Squirtle', rarityCode: 'common', imageUrl: 'https://example.test/squirtle');
        $em->persist($card);
        $em->persist(new OwnedCard($card, Condition::NearMint));
        $em->flush();

        $client->request('GET', '/api/owned_cards?placed=false');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'member' => [
                [
                    '@type' => 'OwnedCard',
                    'card' => [
                        '@type' => 'Card',
                        'name' => 'Squirtle',
                        'imageUrl' => 'https://example.test/squirtle',
                        'numberInSet' => '7',
                    ],
                ],
            ],
        ]);
    }

    public function testPlacedTrueReturnsOnlyPlacedOwnedCards(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder('Test', 5, 3, 3);
        $em->persist($binder);

        $card = CardFixtureFactory::create($em, setId: 'base1', numberInSet: '2', variant: 'normal', language: 'en', name: 'Card 2', rarityCode: 'common');
        $em->persist($card);

        $placedOwnedCard = new OwnedCard($card, Condition::NearMint);
        $em->persist($placedOwnedCard);
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $placedOwnedCard,
        ));

        $freeOwnedCard = new OwnedCard($card, Condition::Mint);
        $em->persist($freeOwnedCard);

        $em->flush();

        $client->request('GET', '/api/owned_cards?placed=true');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['totalItems' => 1]);
        self::assertJsonContains([
            'member' => [
                ['@type' => 'OwnedCard', 'id' => $placedOwnedCard->getId()->toRfc4122()],
            ],
        ]);
    }
}
