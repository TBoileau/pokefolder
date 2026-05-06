<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSets;

use App\Entity\PokemonSet;
use App\Entity\Serie;
use App\Exception\Catalog\SerieNotFoundException;
use App\Repository\PokemonSetRepository;
use App\Repository\SerieRepository;
use App\Service\Catalog\Provider\TCGdexProvider;
use App\UseCase\Catalog\SyncCards\Input as SyncCardsInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Fetches a serie's set list and persists each set + its translation.
 * Dispatches SyncCards per set. Skip-if-exists by default.
 */
#[AsMessageHandler]
final readonly class Handler
{
    public function __construct(
        private TCGdexProvider $tcGdexProvider,
        private EntityManagerInterface $entityManager,
        private SerieRepository $serieRepository,
        private PokemonSetRepository $pokemonSetRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(Input $input): void
    {
        $detail = $this->tcGdexProvider->fetchSerie($input->serieId, $input->language);
        if (!$detail instanceof \App\Service\Catalog\DTO\TCGdexSerieDetail) {
            throw new SerieNotFoundException($input->serieId);
        }

        $serie = $this->serieRepository->find($input->serieId);
        if (!$serie instanceof Serie) {
            // Defensive: SyncSeries should have created it. Create on the fly.
            $serie = new Serie($input->serieId);
            $serie->setLogo($detail->logo);
            $serie->setReleaseDate($detail->releaseDate);
            $serie->upsertTranslation($input->language, $detail->name);
            $this->entityManager->persist($serie);
        }

        foreach ($detail->sets as $resume) {
            $set = $this->pokemonSetRepository->find($resume->id);
            $existedBefore = $set instanceof PokemonSet;
            $hasTranslation = $existedBefore && null !== $set->getTranslation($input->language);

            if ($existedBefore && $hasTranslation && !$input->force) {
                $this->messageBus->dispatch(new SyncCardsInput($resume->id, $input->language, $input->force));
                continue;
            }

            if (!$existedBefore) {
                $set = new PokemonSet($resume->id, $serie);
                $this->entityManager->persist($set);
            }

            if (!$existedBefore || $input->force) {
                $setDetail = $this->tcGdexProvider->fetchSet($resume->id, $input->language);
                if ($setDetail instanceof \App\Service\Catalog\DTO\TCGdexSetDetail) {
                    $set->setLogo($setDetail->logo);
                    $set->setSymbol($setDetail->symbol);
                    $set->setReleaseDate($setDetail->releaseDate);
                    $set->setCardCountTotal($setDetail->cardCountTotal);
                    $set->setCardCountOfficial($setDetail->cardCountOfficial);
                    $set->setLegalStandard($setDetail->legalStandard);
                    $set->setLegalExpanded($setDetail->legalExpanded);
                    $set->setTcgOnlineId($setDetail->tcgOnlineId);
                    $set->upsertTranslation(
                        $input->language,
                        $setDetail->name,
                        $setDetail->abbreviationOfficial,
                        $setDetail->abbreviationNormal,
                    );
                } else {
                    $set->upsertTranslation($input->language, $resume->name);
                }
            } else {
                $set->upsertTranslation($input->language, $resume->name);
            }

            $this->messageBus->dispatch(new SyncCardsInput($resume->id, $input->language, $input->force));
        }

        $this->entityManager->flush();
    }
}
