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
use Symfony\Component\Uid\Uuid;

final class UnplaceControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testUnplaceFreesTheSlotAndKeepsTheOwnedCard(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        [, $ownedCard] = $this->seedPlacedOwnedCard($em);

        $client->request('POST', '/api/owned-cards/'.$ownedCard->getId()->toRfc4122().'/unplace');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $em->clear();
        $remainingSlots = self::getContainer()->get(BinderSlotRepository::class)->findAll();
        self::assertCount(0, $remainingSlots);

        $remainingOwnedCard = $em->find(OwnedCard::class, $ownedCard->getId());
        self::assertNotNull($remainingOwnedCard);
    }

    public function testUnplaceReturns409WhenOwnedCardIsNotInAnySlot(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $ownedCard = $this->seedOwnedCard($em);

        $client->request('POST', '/api/owned-cards/'.$ownedCard->getId()->toRfc4122().'/unplace');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUnplaceReturns404WhenOwnedCardDoesNotExist(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/owned-cards/'.Uuid::v7()->toRfc4122().'/unplace');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array{0: Binder, 1: OwnedCard}
     */
    private function seedPlacedOwnedCard(EntityManagerInterface $em): array
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
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $ownedCard,
        ));
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
