<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSet;

use App\Entity\Card;
use App\Exception\Catalog\SetNotFoundException;
use App\Repository\CardRepository;
use App\Service\Catalog\Provider\TCGdexProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Imports a single TCGdex set into the local Card catalog. Idempotent:
 * re-runs update existing rows in place rather than creating duplicates.
 *
 * Each (card, variant) returned by TCGdex is fanned out to one Card row
 * per configured language; languages where the set is missing are skipped
 * silently. If the set is missing in *every* configured language, a
 * SetNotFoundException is raised.
 *
 * Functional identity: (setId, numberInSet, variant, language).
 */
#[AsMessageHandler]
final readonly class Handler
{
    /**
     * @param list<string> $languages ISO 639-1 codes (e.g. ['en', 'fr']).
     */
    public function __construct(
        private TCGdexProvider $provider,
        private EntityManagerInterface $em,
        private CardRepository $cards,
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private array $languages,
    ) {
    }

    public function __invoke(Input $input): Output
    {
        $output = new Output($input->setId);
        $foundInAnyLanguage = false;

        foreach ($this->languages as $language) {
            $set = $this->provider->fetchSet($input->setId, $language);
            if ($set === null) {
                continue;
            }
            $foundInAnyLanguage = true;

            foreach ($set->cards as $card) {
                foreach ($card->activeVariants as $variant) {
                    $existing = $this->cards->findByFunctionalIdentity(
                        $input->setId,
                        $card->localId,
                        $variant,
                        $language,
                    );

                    if ($existing === null) {
                        $this->em->persist(new Card(
                            setId: $input->setId,
                            numberInSet: $card->localId,
                            variant: $variant,
                            language: $language,
                            name: $card->name,
                            rarity: $card->rarity,
                            imageUrl: $card->imageUrl,
                        ));
                        ++$output->created;

                        continue;
                    }

                    if ($existing->updateCatalogueData($card->name, $card->rarity, $card->imageUrl)) {
                        ++$output->updated;
                    } else {
                        ++$output->unchanged;
                    }
                }
            }
        }

        if (!$foundInAnyLanguage) {
            throw new SetNotFoundException($input->setId);
        }

        $this->em->flush();

        return $output;
    }
}
