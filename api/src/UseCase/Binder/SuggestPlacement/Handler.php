<?php

declare(strict_types=1);

namespace App\UseCase\Binder\SuggestPlacement;

use App\Entity\Binder;
use App\Entity\OwnedCard;
use App\Exception\Binder\OwnedCardNotFoundException;
use App\Repository\BinderRepository;
use App\Repository\OwnedCardRepository;
use App\Service\Binder\PlacementSuggester;
use Symfony\Component\Uid\Uuid;

final readonly class Handler
{
    public function __construct(
        private OwnedCardRepository $ownedCardRepository,
        private BinderRepository $binderRepository,
        private PlacementSuggester $suggester,
    ) {
    }

    public function __invoke(Input $input): Output
    {
        $ownedCard = $this->ownedCardRepository->find(Uuid::fromString($input->ownedCardId));
        if (!$ownedCard instanceof OwnedCard) {
            throw new OwnedCardNotFoundException($input->ownedCardId);
        }

        /** @var list<Binder> $binders */
        $binders = $this->binderRepository->findBy([], ['createdAt' => 'ASC']);

        $suggestion = $this->suggester->suggest($ownedCard, $binders);

        return new Output($suggestion?->getId()->toRfc4122());
    }
}
