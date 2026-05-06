<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Entity\Binder;

/**
 * Read-side surface that PlacementSuggester needs to score binders. The
 * production implementation queries `binder_slot`; pure unit tests pass
 * an in-memory adapter.
 */
interface BinderInventoryInterface
{
    /**
     * Distinct setIds of the Cards currently placed in $binder.
     *
     * @return list<string>
     */
    public function setIdsInBinder(Binder $binder): array;

    /**
     * Number of slots in $binder that currently host an OwnedCard.
     */
    public function occupiedCountFor(Binder $binder): int;
}
