<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Entity\BinderSlot;

/**
 * Outcome of a BinderPlacementService::move() call. The previous slot
 * (if any) is the one whose OwnedCard has just been detached — callers
 * should remove it. The new slot is the one to persist.
 */
final readonly class PlacementMoveResult
{
    public function __construct(
        public ?BinderSlot $previousSlot,
        public BinderSlot $newSlot,
    ) {
    }
}
