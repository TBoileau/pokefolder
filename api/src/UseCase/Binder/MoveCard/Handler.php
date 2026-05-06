<?php

declare(strict_types=1);

namespace App\UseCase\Binder\MoveCard;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Exception\Binder\BinderNotFoundException;
use App\Exception\Binder\OwnedCardNotFoundException;
use App\Repository\BinderRepository;
use App\Repository\OwnedCardRepository;
use App\Service\Binder\BinderPlacementService;
use App\Service\Binder\BinderSlotPosition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Atomic move of an OwnedCard between (or within) binders. The detach
 * and the (re)attach run inside a single Doctrine transaction; the
 * delete-then-insert sequence keeps the unique constraint on owned_card_id
 * happy under any UoW ordering.
 */
final readonly class Handler
{
    public function __construct(
        private OwnedCardRepository $ownedCardRepository,
        private BinderRepository $binderRepository,
        private BinderPlacementService $placementService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Input $input): Output
    {
        $ownedCard = $this->ownedCardRepository->find(Uuid::fromString($input->ownedCardId));
        if (!$ownedCard instanceof OwnedCard) {
            throw new OwnedCardNotFoundException($input->ownedCardId);
        }

        $binder = $this->binderRepository->find(Uuid::fromString($input->binderId));
        if (!$binder instanceof Binder) {
            throw new BinderNotFoundException($input->binderId);
        }

        $position = new BinderSlotPosition(
            pageNumber: $input->pageNumber,
            face: $input->face,
            row: $input->row,
            col: $input->col,
        );

        $entityManager = $this->entityManager;

        /** @var BinderSlot $newSlot */
        $newSlot = $entityManager->wrapInTransaction(function () use ($entityManager, $ownedCard, $binder, $position): BinderSlot {
            $result = $this->placementService->move($ownedCard, $binder, $position);

            if ($result->previousSlot instanceof BinderSlot && $result->previousSlot !== $result->newSlot) {
                $entityManager->remove($result->previousSlot);
                $entityManager->flush();
            }

            $entityManager->persist($result->newSlot);
            $entityManager->flush();

            return $result->newSlot;
        });

        return new Output(
            slotId: $newSlot->getId()->toRfc4122(),
            binderId: $binder->getId()->toRfc4122(),
            ownedCardId: $ownedCard->getId()->toRfc4122(),
            pageNumber: $newSlot->getPageNumber(),
            face: $newSlot->getFace(),
            row: $newSlot->getRow(),
            col: $newSlot->getCol(),
        );
    }
}
