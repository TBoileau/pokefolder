<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;

/**
 * Read-side surface that BinderPlacementService needs to enforce its
 * invariants. Implemented by BinderSlotRepository in production and
 * by an in-memory fake in pure unit tests — keeps the service kernel-free.
 */
interface BinderSlotLookupInterface
{
    public function findByOwnedCard(OwnedCard $ownedCard): ?BinderSlot;

    public function findByPosition(Binder $binder, BinderSlotPosition $position): ?BinderSlot;
}
