<?php

declare(strict_types=1);

namespace App\UseCase\Binder\UnplaceCard;

use App\Entity\OwnedCard;
use App\Exception\Binder\OwnedCardNotFoundException;
use App\Repository\OwnedCardRepository;
use App\Service\Binder\BinderPlacementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class Handler
{
    public function __construct(
        private OwnedCardRepository $ownedCardRepository,
        private BinderPlacementService $placementService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Input $input): void
    {
        $ownedCard = $this->ownedCardRepository->find(Uuid::fromString($input->ownedCardId));
        if (!$ownedCard instanceof OwnedCard) {
            throw new OwnedCardNotFoundException($input->ownedCardId);
        }

        $slot = $this->placementService->remove($ownedCard);
        $this->entityManager->remove($slot);
        $this->entityManager->flush();
    }
}
