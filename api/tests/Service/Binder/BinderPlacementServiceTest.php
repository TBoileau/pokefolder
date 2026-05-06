<?php

declare(strict_types=1);

namespace App\Tests\Service\Binder;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Enum\Condition;
use App\Exception\Binder\OwnedCardAlreadyPlacedException;
use App\Exception\Binder\PositionOutOfBoundsException;
use App\Exception\Binder\SlotAlreadyOccupiedException;
use App\Service\Binder\BinderPlacementService;
use App\Service\Binder\BinderSlotPosition;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for BinderPlacementService — no kernel, no DB. Each
 * test wires the service against an in-memory lookup so we can probe
 * every invariant in isolation.
 */
final class BinderPlacementServiceTest extends TestCase
{
    private InMemoryBinderSlotLookup $lookup;

    private BinderPlacementService $service;

    protected function setUp(): void
    {
        $this->lookup = new InMemoryBinderSlotLookup();
        $this->service = new BinderPlacementService($this->lookup);
    }

    public function testPlacesOwnedCardInEmptySlot(): void
    {
        $binder = $this->makeBinder(pageCount: 2, cols: 3, rows: 3, doubleSided: true);
        $ownedCard = $this->makeOwnedCard();
        $position = new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1);

        $slot = $this->service->place($ownedCard, $binder, $position);

        self::assertSame($binder, $slot->getBinder());
        self::assertSame($ownedCard, $slot->getOwnedCard());
        self::assertSame(1, $slot->getPageNumber());
        self::assertSame(BinderSlotFace::Recto, $slot->getFace());
        self::assertSame(1, $slot->getRow());
        self::assertSame(1, $slot->getCol());
    }

    public function testRejectsSlotAlreadyOccupied(): void
    {
        $binder = $this->makeBinder();
        $occupied = new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $this->makeOwnedCard(),
        );
        $this->lookup->add($occupied);

        $this->expectException(SlotAlreadyOccupiedException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
        );
    }

    public function testRejectsOwnedCardAlreadyPlaced(): void
    {
        $binder = $this->makeBinder();
        $ownedCard = $this->makeOwnedCard();
        $existing = new BinderSlot(
            $binder,
            new BinderSlotPosition(2, BinderSlotFace::Verso, 2, 2),
            $ownedCard,
        );
        $this->lookup->add($existing);

        $this->expectException(OwnedCardAlreadyPlacedException::class);
        $this->service->place(
            $ownedCard,
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
        );
    }

    public function testRejectsPageNumberOutOfBounds(): void
    {
        $binder = $this->makeBinder(pageCount: 2);

        $this->expectException(PositionOutOfBoundsException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(3, BinderSlotFace::Recto, 1, 1),
        );
    }

    public function testRejectsRowOutOfBounds(): void
    {
        $binder = $this->makeBinder(rows: 3);

        $this->expectException(PositionOutOfBoundsException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 4, 1),
        );
    }

    public function testRejectsColOutOfBounds(): void
    {
        $binder = $this->makeBinder(cols: 3);

        $this->expectException(PositionOutOfBoundsException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 4),
        );
    }

    public function testRejectsZeroIndexedPosition(): void
    {
        $binder = $this->makeBinder();

        $this->expectException(PositionOutOfBoundsException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 0, 1),
        );
    }

    public function testRejectsVersoOnNonDoubleSidedBinder(): void
    {
        $binder = $this->makeBinder(doubleSided: false);

        $this->expectException(PositionOutOfBoundsException::class);
        $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Verso, 1, 1),
        );
    }

    public function testAcceptsVersoOnDoubleSidedBinder(): void
    {
        $binder = $this->makeBinder(doubleSided: true);

        $slot = $this->service->place(
            $this->makeOwnedCard(),
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Verso, 1, 1),
        );

        self::assertSame(BinderSlotFace::Verso, $slot->getFace());
    }

    private function makeBinder(
        int $pageCount = 10,
        int $cols = 3,
        int $rows = 3,
        bool $doubleSided = true,
    ): Binder {
        return new Binder(
            name: 'Test binder',
            pageCount: $pageCount,
            cols: $cols,
            rows: $rows,
            doubleSided: $doubleSided,
        );
    }

    private function makeOwnedCard(): OwnedCard
    {
        $card = new Card(
            setId: 'base1',
            numberInSet: '4',
            variant: 'normal',
            language: 'en',
            name: 'Charizard',
            rarity: 'Rare Holo',
        );

        return new OwnedCard(card: $card, condition: Condition::NearMint);
    }
}
