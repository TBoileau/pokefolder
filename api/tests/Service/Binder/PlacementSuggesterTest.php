<?php

declare(strict_types=1);

namespace App\Tests\Service\Binder;

use App\Entity\Binder;
use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\Condition;
use App\Service\Binder\PlacementSuggester;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for PlacementSuggester — no kernel, no DB. The suggester
 * is fed an in-memory inventory so we can probe each branch of the
 * "first binder with set match AND free slot" heuristic.
 */
final class PlacementSuggesterTest extends TestCase
{
    private InMemoryBinderInventory $inventory;

    private PlacementSuggester $suggester;

    protected function setUp(): void
    {
        $this->inventory = new InMemoryBinderInventory();
        $this->suggester = new PlacementSuggester($this->inventory);
    }

    public function testReturnsNullWhenNoBindersExist(): void
    {
        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), []);

        self::assertNull($result);
    }

    public function testReturnsNullWhenNoBinderContainsCardOfTheSameSet(): void
    {
        $binder = $this->makeBinder();
        $this->inventory->record($binder, ['baseSet'], 0);

        $result = $this->suggester->suggest($this->makeOwnedCard('jungle'), [$binder]);

        self::assertNull($result);
    }

    public function testReturnsNullWhenAllMatchingBindersAreFull(): void
    {
        $binder = $this->makeBinder(pageCount: 1, cols: 1, rows: 1, doubleSided: false);
        $this->inventory->record($binder, ['base1'], 1);

        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), [$binder]);

        self::assertNull($result);
    }

    public function testReturnsTheFirstMatchingBinderWithFreeCapacity(): void
    {
        $first = $this->makeBinder();
        $this->inventory->record($first, ['base1'], 0);

        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), [$first]);

        self::assertSame($first, $result);
    }

    public function testPrefersInputOrderWhenMultipleBindersMatch(): void
    {
        $older = $this->makeBinder();
        $newer = $this->makeBinder();
        $this->inventory->record($older, ['base1'], 0);
        $this->inventory->record($newer, ['base1'], 0);

        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), [$older, $newer]);

        self::assertSame($older, $result);
    }

    public function testSkipsFullBinderInFavorOfNextMatching(): void
    {
        $full = $this->makeBinder(pageCount: 1, cols: 1, rows: 1, doubleSided: false);
        $available = $this->makeBinder();
        $this->inventory->record($full, ['base1'], 1);
        $this->inventory->record($available, ['base1'], 0);

        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), [$full, $available]);

        self::assertSame($available, $result);
    }

    public function testIgnoresBindersThatDontHoldTheTargetSetEvenIfFree(): void
    {
        $unrelated = $this->makeBinder();
        $matching = $this->makeBinder();
        $this->inventory->record($unrelated, ['jungle'], 0);
        $this->inventory->record($matching, ['base1', 'fossil'], 0);

        $result = $this->suggester->suggest($this->makeOwnedCard('base1'), [$unrelated, $matching]);

        self::assertSame($matching, $result);
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

    private function makeOwnedCard(string $setId): OwnedCard
    {
        $card = new Card(
            setId: $setId,
            numberInSet: '1',
            variant: 'normal',
            language: 'en',
            name: 'Test card',
            rarity: 'Common',
        );

        return new OwnedCard(card: $card, condition: Condition::NearMint);
    }
}
