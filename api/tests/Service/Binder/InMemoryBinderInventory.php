<?php

declare(strict_types=1);

namespace App\Tests\Service\Binder;

use App\Entity\Binder;
use App\Service\Binder\BinderInventoryInterface;
use SplObjectStorage;

/**
 * In-memory test double for BinderInventoryInterface — fed by the test
 * which assigns each binder its current setIds and occupied count up
 * front. Lets PlacementSuggester run without booting the kernel.
 *
 * @internal
 */
final class InMemoryBinderInventory implements BinderInventoryInterface
{
    /**
     * @var SplObjectStorage<Binder, list<string>>
     */
    private SplObjectStorage $setIdsByBinder;

    /**
     * @var SplObjectStorage<Binder, int>
     */
    private SplObjectStorage $occupiedByBinder;

    public function __construct()
    {
        $this->setIdsByBinder = new SplObjectStorage();
        $this->occupiedByBinder = new SplObjectStorage();
    }

    /**
     * @param list<string> $setIds
     */
    public function record(Binder $binder, array $setIds, int $occupied): void
    {
        $this->setIdsByBinder[$binder] = $setIds;
        $this->occupiedByBinder[$binder] = $occupied;
    }

    public function setIdsInBinder(Binder $binder): array
    {
        return $this->setIdsByBinder[$binder] ?? [];
    }

    public function occupiedCountFor(Binder $binder): int
    {
        return $this->occupiedByBinder[$binder] ?? 0;
    }
}
