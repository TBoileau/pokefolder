<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Catalog\Provider\TCGdexProvider;
use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Imports a TCGdex set's cards into the local catalogue. Idempotent:
 * re-running for the same set updates existing rows in place rather than
 * creating duplicates. The functional identity is
 * (set, numberInSet, variant, language).
 *
 * Each `(card, variant)` returned by TCGdex is fanned out to one row per
 * configured language. A set that is missing in a given language is
 * skipped silently; if it is missing in *every* configured language, a
 * SetNotFoundException is raised.
 */
final class CatalogSynchronizer
{
    /**
     * @param list<string> $languages ISO 639-1 codes (e.g. ['en', 'fr']).
     */
    public function __construct(
        private readonly TCGdexProvider $provider,
        private readonly EntityManagerInterface $em,
        private readonly CardRepository $cards,
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private readonly array $languages,
    ) {
    }

    public function syncSet(string $setId): SyncReport
    {
        $report = new SyncReport($setId);
        $foundInAnyLanguage = false;

        foreach ($this->languages as $language) {
            $set = $this->provider->fetchSet($setId, $language);
            if ($set === null) {
                continue;
            }
            $foundInAnyLanguage = true;

            foreach ($set->cards as $card) {
                foreach ($card->activeVariants as $variant) {
                    $existing = $this->cards->findByFunctionalIdentity(
                        $setId,
                        $card->localId,
                        $variant,
                        $language,
                    );

                    if ($existing === null) {
                        $this->em->persist(new Card(
                            set: $setId,
                            numberInSet: $card->localId,
                            variant: $variant,
                            language: $language,
                            name: $card->name,
                            rarity: $card->rarity,
                            imageUrl: $card->imageUrl,
                        ));
                        ++$report->created;

                        continue;
                    }

                    if ($existing->updateCatalogueData($card->name, $card->rarity, $card->imageUrl)) {
                        ++$report->updated;
                    } else {
                        ++$report->unchanged;
                    }
                }
            }
        }

        if (!$foundInAnyLanguage) {
            throw new SetNotFoundException($setId);
        }

        $this->em->flush();

        return $report;
    }
}
