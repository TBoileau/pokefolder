<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Entity\Binder;
use App\Entity\OwnedCard;

use function in_array;

/**
 * Picks a binder to suggest as the placement target for an OwnedCard.
 *
 * Heuristic: the first binder (in input order) that already contains at
 * least one card from the same set AND has at least one free slot.
 * Returns null if nothing matches — callers fall back to "create a new
 * binder" or manual placement.
 */
final readonly class PlacementSuggester
{
    public function __construct(private BinderInventoryInterface $inventory)
    {
    }

    /**
     * @param list<Binder> $binders
     */
    public function suggest(OwnedCard $ownedCard, array $binders): ?Binder
    {
        $targetSetId = $ownedCard->getCard()->getSetId();

        foreach ($binders as $binder) {
            $setIds = $this->inventory->setIdsInBinder($binder);
            if (!in_array($targetSetId, $setIds, true)) {
                continue;
            }

            if ($this->inventory->occupiedCountFor($binder) >= $binder->getCapacity()) {
                continue;
            }

            return $binder;
        }

        return null;
    }
}
