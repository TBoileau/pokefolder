<?php

declare(strict_types=1);

namespace App\Tests\Service\Binder;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Service\Binder\BinderSlotLookupInterface;
use App\Service\Binder\BinderSlotPosition;

/**
 * In-memory test double for BinderSlotLookupInterface — backs the pure
 * unit tests around BinderPlacementService without booting the kernel.
 *
 * @internal
 */
final class InMemoryBinderSlotLookup implements BinderSlotLookupInterface
{
    /**
     * @var list<BinderSlot>
     */
    private array $slots = [];

    public function add(BinderSlot $slot): void
    {
        $this->slots[] = $slot;
    }

    public function findByOwnedCard(OwnedCard $ownedCard): ?BinderSlot
    {
        foreach ($this->slots as $slot) {
            if ($slot->getOwnedCard() === $ownedCard) {
                return $slot;
            }
        }

        return null;
    }

    public function findByPosition(Binder $binder, BinderSlotPosition $position): ?BinderSlot
    {
        foreach ($this->slots as $slot) {
            if ($slot->getBinder() === $binder && $slot->getPosition()->equals($position)) {
                return $slot;
            }
        }

        return null;
    }
}
