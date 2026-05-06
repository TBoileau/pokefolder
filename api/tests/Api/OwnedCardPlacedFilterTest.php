<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Enum\Condition;
use App\Service\Binder\BinderSlotPosition;
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

        $card = new Card('base1', '1', 'normal', 'en', 'Card 1', 'Common');
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

    public function testPlacedTrueReturnsOnlyPlacedOwnedCards(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder('Test', 5, 3, 3);
        $em->persist($binder);

        $card = new Card('base1', '2', 'normal', 'en', 'Card 2', 'Common');
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
