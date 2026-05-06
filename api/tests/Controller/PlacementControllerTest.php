<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Enum\Condition;
use App\Repository\BinderSlotRepository;
use App\Tests\Support\CardFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class PlacementControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testPlaceCreatesSlotAtRequestedPosition(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $ownedCard] = $this->seedBinderAndOwnedCard($em);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $ownedCard->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJsonContains([
            'binderId' => $binder->getId()->toRfc4122(),
            'ownedCardId' => $ownedCard->getId()->toRfc4122(),
            'pageNumber' => 1,
            'face' => 'recto',
            'row' => 1,
            'col' => 1,
        ]);

        $slots = self::getContainer()->get(BinderSlotRepository::class)->findAll();
        self::assertCount(1, $slots);
    }

    public function testPlaceReturns409WhenSlotIsAlreadyOccupied(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $first] = $this->seedBinderAndOwnedCard($em);
        $second = $this->seedOwnedCard($em);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $first->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $second->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testPlaceReturns422WhenPositionIsOutOfBounds(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $ownedCard] = $this->seedBinderAndOwnedCard($em);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $ownedCard->getId()->toRfc4122(),
                'pageNumber' => 999,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPlaceReturns404WhenBinderDoesNotExist(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $ownedCard = $this->seedOwnedCard($em);

        $client->request('POST', '/api/binders/'.Uuid::v7()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $ownedCard->getId()->toRfc4122(),
                'pageNumber' => 1,
                'face' => 'recto',
                'row' => 1,
                'col' => 1,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPlaceReturns400OnMalformedBody(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder] = $this->seedBinderAndOwnedCard($em);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['ownedCardId' => 'not-relevant'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSlotAndOwnedCardSurviveListingAfterPlacement(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [$binder, $ownedCard] = $this->seedBinderAndOwnedCard($em);

        $client->request('POST', '/api/binders/'.$binder->getId()->toRfc4122().'/place', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'ownedCardId' => $ownedCard->getId()->toRfc4122(),
                'pageNumber' => 2,
                'face' => 'verso',
                'row' => 2,
                'col' => 3,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $em->clear();
        $slot = self::getContainer()->get(BinderSlotRepository::class)->findOneBy([]);
        self::assertInstanceOf(BinderSlot::class, $slot);
        self::assertSame(2, $slot->getPageNumber());
        self::assertSame(2, $slot->getRow());
        self::assertSame(3, $slot->getCol());
    }

    /**
     * @return array{0: Binder, 1: OwnedCard}
     */
    private function seedBinderAndOwnedCard(EntityManagerInterface $em): array
    {
        $binder = new Binder(
            name: 'Test binder',
            pageCount: 10,
            cols: 3,
            rows: 3,
            doubleSided: true,
        );
        $em->persist($binder);
        $ownedCard = $this->seedOwnedCard($em);
        $em->flush();

        return [$binder, $ownedCard];
    }

    private function seedOwnedCard(EntityManagerInterface $em): OwnedCard
    {
        $card = CardFixtureFactory::create(
            $em,
            setId: 'base1',
            numberInSet: bin2hex(random_bytes(3)),
            variant: 'normal',
            language: 'en',
            name: 'Charizard',
            rarityCode: 'rare-holo',
        );
        $em->persist($card);
        $owned = new OwnedCard(card: $card, condition: Condition::NearMint);
        $em->persist($owned);
        $em->flush();

        return $owned;
    }
}
