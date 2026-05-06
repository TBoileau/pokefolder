<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSeries;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use App\Service\Catalog\Provider\TCGdexProvider;
use App\UseCase\Catalog\SyncSets\Input as SyncSetsInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Lists all TCGdex series in the given language, upserts each one + its
 * translation, and dispatches a SyncSets per serie. Skip-if-exists by
 * default; force=true refreshes existing rows.
 */
#[AsMessageHandler]
final readonly class Handler
{
    public function __construct(
        private TCGdexProvider $tcGdexProvider,
        private EntityManagerInterface $entityManager,
        private SerieRepository $serieRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(Input $input): void
    {
        $resumes = $this->tcGdexProvider->listSeries($input->language);

        foreach ($resumes as $resume) {
            $serie = $this->serieRepository->find($resume->id);
            $existedBefore = $serie instanceof Serie;
            $hasTranslation = $existedBefore && null !== $serie->getTranslation($input->language);

            if ($existedBefore && $hasTranslation && !$input->force) {
                $this->messageBus->dispatch(new SyncSetsInput($resume->id, $input->language, $input->force));
                continue;
            }

            if (!$existedBefore) {
                $serie = new Serie($resume->id);
                $this->entityManager->persist($serie);
            }

            // Pull the full detail to populate logo + releaseDate when first seen.
            if (!$existedBefore || $input->force) {
                $detail = $this->tcGdexProvider->fetchSerie($resume->id, $input->language);
                if ($detail instanceof \App\Service\Catalog\DTO\TCGdexSerieDetail) {
                    $serie->setLogo($detail->logo);
                    $serie->setReleaseDate($detail->releaseDate);
                }
            }

            $serie->upsertTranslation($input->language, $resume->name);
            $this->messageBus->dispatch(new SyncSetsInput($resume->id, $input->language, $input->force));
        }

        $this->entityManager->flush();
    }
}
