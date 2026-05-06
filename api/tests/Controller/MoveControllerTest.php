<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Enum\Condition;
use App\Repository\BinderSlotRepository;
use App\Service\Binder\BinderSlotPosition;
use App\Tests\Support\CardFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class MoveControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testMoveDetachesAndReattachesAtomically(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $ownedCard] = $this->seedPlacedOwnedCard($em);

        $client->request('POST', '/api/owned-cards/'.$ownedCard->getId()->toRfc4122().'/move', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'binderId' => $binder->getId()->toRfc4122(),
                'pageNumber' => 3,
                'face' => 'verso',
                'row' => 2,
                'col' => 2,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'binderId' => $binder->getId()->toRfc4122(),
            'ownedCardId' => $ownedCard->getId()->toRfc4122(),
            'pageNumber' => 3,
            'face' => 'verso',
            'row' => 2,
            'col' => 2,
        ]);

        $em->clear();
        $slots = self::getContainer()->get(BinderSlotRepository::class)->findAll();
        self::assertCount(1, $slots);
        self::assertSame(3, $slots[0]->getPageNumber());
    }

    public function testMoveBehavesLikePlaceWhenOwnedCardIsNotYetPlaced(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $binder = $this->seedBinder($em);
        $ownedCard = $this->seedOwnedCard($em);

        $client->request('POST', '/api/owned-cards/'.$ownedCard->getId()->toRfc4122().'/move', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'binderId' => $binder->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $slots = self::getContainer()->get(BinderSlotRepository::class)->findAll();
        self::assertCount(1, $slots);
    }

    public function testMoveReturns409WhenTargetSlotIsOccupiedByAnotherCard(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $binder = $this->seedBinder($em);

        $occupant = $this->seedOwnedCard($em);
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $occupant,
        ));

        $movingCard = $this->seedOwnedCard($em);
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(2, BinderSlotFace::Recto, 1, 1),
            $movingCard,
        ));
        $em->flush();

        $client->request('POST', '/api/owned-cards/'.$movingCard->getId()->toRfc4122().'/move', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'binderId' => $binder->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $em->clear();
        $slots = self::getContainer()->get(BinderSlotRepository::class)->findAll();
        self::assertCount(2, $slots);
    }

    public function testMoveReturns422WhenTargetIsOutOfBounds(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $ownedCard] = $this->seedPlacedOwnedCard($em);

        $client->request('POST', '/api/owned-cards/'.$ownedCard->getId()->toRfc4122().'/move', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'binderId' => $binder->getId()->toRfc4122(),
                'pageNumber' => 999,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array{0: Binder, 1: OwnedCard}
     */
    private function seedPlacedOwnedCard(EntityManagerInterface $em): array
    {
        $binder = $this->seedBinder($em);
        $ownedCard = $this->seedOwnedCard($em);
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $ownedCard,
        ));
        $em->flush();

        return [$binder, $ownedCard];
    }

    private function seedBinder(EntityManagerInterface $em): Binder
    {
        $binder = new Binder(
            name: 'Test binder',
            pageCount: 10,
            cols: 3,
            rows: 3,
            doubleSided: true,
        );
        $em->persist($binder);
        $em->flush();

        return $binder;
    }

    private function seedOwnedCard(EntityManagerInterface $em): OwnedCard
    {
        $card = CardFixtureFactory::create(
            $em,
            setId: 'base1',
            numberInSet: bin2hex(random_bytes(3)),
            variant: 'normal',
            language: 'en',
            name: 'Test card',
            rarityCode: 'common',
        );
        $em->persist($card);
        $owned = new OwnedCard(card: $card, condition: Condition::NearMint);
        $em->persist($owned);
        $em->flush();

        return $owned;
    }
}
