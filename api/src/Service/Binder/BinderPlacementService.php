<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Exception\Binder\OwnedCardAlreadyPlacedException;
use App\Exception\Binder\OwnedCardNotPlacedException;
use App\Exception\Binder\PositionOutOfBoundsException;
use App\Exception\Binder\SlotAlreadyOccupiedException;

/**
 * Pure domain service for placing OwnedCards into BinderSlots. Holds the
 * three placement invariants — bounds, slot vacancy, and the 1-OwnedCard ↔
 * 1-slot rule — without touching Doctrine itself; persistence is the
 * caller's responsibility (typically the UseCase Handler).
 */
final readonly class BinderPlacementService
{
    public function __construct(private BinderSlotLookupInterface $lookup)
    {
    }

    /**
     * Reserves a slot at $position in $binder for $ownedCard.
     *
     * Returns either a fresh BinderSlot (caller must persist it) or an
     * existing one whose ownedCard has just been (re)set — both work the
     * same from the caller's perspective via EntityManager::persist().
     *
     * @throws OwnedCardAlreadyPlacedException
     * @throws PositionOutOfBoundsException
     * @throws SlotAlreadyOccupiedException
     */
    public function place(OwnedCard $ownedCard, Binder $binder, BinderSlotPosition $position): BinderSlot
    {
        $this->assertWithinBounds($binder, $position);

        $existingForOwnedCard = $this->lookup->findByOwnedCard($ownedCard);
        if ($existingForOwnedCard instanceof BinderSlot) {
            throw new OwnedCardAlreadyPlacedException($ownedCard);
        }

        $existingAtPosition = $this->lookup->findByPosition($binder, $position);
        if ($existingAtPosition instanceof BinderSlot && $existingAtPosition->getOwnedCard() instanceof OwnedCard) {
            throw new SlotAlreadyOccupiedException($binder, $position);
        }

        if ($existingAtPosition instanceof BinderSlot) {
            $existingAtPosition->setOwnedCard($ownedCard);

            return $existingAtPosition;
        }

        return new BinderSlot($binder, $position, $ownedCard);
    }

    /**
     * Frees the slot occupied by $ownedCard. Returns the slot the caller
     * is expected to remove from the EntityManager. The OwnedCard itself
     * stays in the collection — only the binder placement is undone.
     *
     * @throws OwnedCardNotPlacedException
     */
    public function remove(OwnedCard $ownedCard): BinderSlot
    {
        $slot = $this->lookup->findByOwnedCard($ownedCard);
        if (!$slot instanceof BinderSlot) {
            throw new OwnedCardNotPlacedException($ownedCard);
        }

        return $slot;
    }

    /**
     * Moves $ownedCard to ($binder, $position). If the OwnedCard was not
     * placed, behaves like place(). The PlacementMoveResult tells the
     * caller which slot (if any) to delete and which slot to persist —
     * atomicity is the caller's responsibility (Doctrine transaction).
     *
     * @throws PositionOutOfBoundsException
     * @throws SlotAlreadyOccupiedException
     */
    public function move(OwnedCard $ownedCard, Binder $binder, BinderSlotPosition $position): PlacementMoveResult
    {
        $this->assertWithinBounds($binder, $position);

        $previousSlot = $this->lookup->findByOwnedCard($ownedCard);

        if ($previousSlot instanceof BinderSlot
            && $previousSlot->getBinder() === $binder
            && $previousSlot->getPosition()->equals($position)
        ) {
            return new PlacementMoveResult(previousSlot: null, newSlot: $previousSlot);
        }

        $existingAtPosition = $this->lookup->findByPosition($binder, $position);
        if ($existingAtPosition instanceof BinderSlot
            && $existingAtPosition->getOwnedCard() instanceof OwnedCard
            && $existingAtPosition !== $previousSlot
        ) {
            throw new SlotAlreadyOccupiedException($binder, $position);
        }

        if ($existingAtPosition instanceof BinderSlot) {
            $existingAtPosition->setOwnedCard($ownedCard);
            $newSlot = $existingAtPosition;
        } else {
            $newSlot = new BinderSlot($binder, $position, $ownedCard);
        }

        return new PlacementMoveResult(previousSlot: $previousSlot, newSlot: $newSlot);
    }

    private function assertWithinBounds(Binder $binder, BinderSlotPosition $position): void
    {
        if ($position->pageNumber < 1 || $position->pageNumber > $binder->getPageCount()) {
            throw new PositionOutOfBoundsException($binder, $position, 'pageNumber outside binder pages');
        }

        if ($position->row < 1 || $position->row > $binder->getRows()) {
            throw new PositionOutOfBoundsException($binder, $position, 'row outside binder grid');
        }

        if ($position->col < 1 || $position->col > $binder->getCols()) {
            throw new PositionOutOfBoundsException($binder, $position, 'col outside binder grid');
        }

        if (BinderSlotFace::Verso === $position->face && !$binder->isDoubleSided()) {
            throw new PositionOutOfBoundsException($binder, $position, 'verso face on non double-sided binder');
        }
    }
}
