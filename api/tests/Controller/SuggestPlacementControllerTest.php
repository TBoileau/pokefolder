<?php

declare(strict_types=1);

namespace App\Tests\Controller;

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
use Symfony\Component\Uid\Uuid;

final class SuggestPlacementControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testReturnsBinderIdWhenAMatchingBinderHasFreeSpace(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = $this->seedBinder($em);
        $existing = $this->seedOwnedCard($em, setId: 'base1');
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $existing,
        ));
        $candidate = $this->seedOwnedCard($em, setId: 'base1');
        $em->flush();

        $client->request('POST', '/api/owned-cards/'.$candidate->getId()->toRfc4122().'/suggest-placement');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['binderId' => $binder->getId()->toRfc4122()]);
    }

    public function testReturnsNullBinderIdWhenNoBinderHoldsTheTargetSet(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = $this->seedBinder($em);
        $unrelated = $this->seedOwnedCard($em, setId: 'jungle');
        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $unrelated,
        ));
        $candidate = $this->seedOwnedCard($em, setId: 'base1');
        $em->flush();

        $client->request('POST', '/api/owned-cards/'.$candidate->getId()->toRfc4122().'/suggest-placement');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains(['binderId' => null]);
    }

    public function testReturns404WhenOwnedCardDoesNotExist(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/owned-cards/'.Uuid::v7()->toRfc4122().'/suggest-placement');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
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

    private function seedOwnedCard(EntityManagerInterface $em, string $setId): OwnedCard
    {
        $card = CardFixtureFactory::create(
            $em,
            setId: $setId,
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
