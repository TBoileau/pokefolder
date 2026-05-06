<?php

declare(strict_types=1);

namespace App\UseCase\Binder\PlaceCard;

use App\Entity\Binder;
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
 * Synchronous use case: looks up the Binder and OwnedCard from their
 * RFC 4122 IDs, delegates the placement invariants to BinderPlacementService,
 * then persists the resulting BinderSlot.
 */
final readonly class Handler
{
    public function __construct(
        private BinderRepository $binderRepository,
        private OwnedCardRepository $ownedCardRepository,
        private BinderPlacementService $placementService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Input $input): Output
    {
        $binder = $this->binderRepository->find(Uuid::fromString($input->binderId));
        if (!$binder instanceof Binder) {
            throw new BinderNotFoundException($input->binderId);
        }

        $ownedCard = $this->ownedCardRepository->find(Uuid::fromString($input->ownedCardId));
        if (!$ownedCard instanceof OwnedCard) {
            throw new OwnedCardNotFoundException($input->ownedCardId);
        }

        $position = new BinderSlotPosition(
            pageNumber: $input->pageNumber,
            face: $input->face,
            row: $input->row,
            col: $input->col,
        );

        $binderSlot = $this->placementService->place($ownedCard, $binder, $position);
        $this->entityManager->persist($binderSlot);
        $this->entityManager->flush();

        return new Output(
            slotId: $binderSlot->getId()->toRfc4122(),
            binderId: $binder->getId()->toRfc4122(),
            ownedCardId: $ownedCard->getId()->toRfc4122(),
            pageNumber: $binderSlot->getPageNumber(),
            face: $binderSlot->getFace(),
            row: $binderSlot->getRow(),
            col: $binderSlot->getCol(),
        );
    }
}
