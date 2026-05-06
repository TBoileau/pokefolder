<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncCards;

use App\Entity\Card;
use App\Entity\PokemonSet;
use App\Entity\Rarity;
use App\Exception\Catalog\SetNotFoundException;
use App\Repository\CardRepository;
use App\Repository\PokemonSetRepository;
use App\Repository\RarityRepository;
use App\Service\Catalog\Provider\TCGdexProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * For a given set + language, fetches the card listing and upserts each
 * card. Skip-if-exists by default: a (set, localId, variant, language)
 * already known is left untouched. force=true triggers a full refetch
 * via tcgdex.card.get for every entry.
 */
#[AsMessageHandler]
final readonly class Handler
{
    public function __construct(
        private TCGdexProvider $tcGdexProvider,
        private EntityManagerInterface $entityManager,
        private PokemonSetRepository $pokemonSetRepository,
        private CardRepository $cardRepository,
        private RarityRepository $rarityRepository,
    ) {
    }

    public function __invoke(Input $input): void
    {
        $set = $this->pokemonSetRepository->find($input->setId);
        if (!$set instanceof PokemonSet) {
            throw new SetNotFoundException($input->setId);
        }

        $detail = $this->tcGdexProvider->fetchSet($input->setId, $input->language);
        if (!$detail instanceof \App\Service\Catalog\DTO\TCGdexSetDetail) {
            throw new SetNotFoundException($input->setId);
        }

        foreach ($detail->cards as $resume) {
            // Skip-if-exists fast-path: avoid the per-card detail fetch when
            // we already have at least one variant of this (set, localId,
            // language) and no force was requested.
            if (!$input->force && $this->anyVariantExists($set, $resume->localId, $input->language)) {
                continue;
            }

            $card = $this->tcGdexProvider->fetchCard($input->setId, $resume->localId, $input->language);
            if (!$card instanceof \App\Service\Catalog\DTO\TCGdexCard) {
                continue;
            }

            $rarity = null !== $card->rarity ? $this->upsertRarity($card->rarity, $input->language) : null;

            foreach ($card->activeVariants as $variant) {
                $existing = $this->cardRepository->findByFunctionalIdentity(
                    $set,
                    $resume->localId,
                    $variant,
                    $input->language,
                );

                if ($existing instanceof Card) {
                    if ($input->force) {
                        $existing->updateCatalogueData($card->name, $rarity, $card->imageUrl);
                    }

                    continue;
                }

                $this->entityManager->persist(new Card(
                    pokemonSet: $set,
                    numberInSet: $resume->localId,
                    variant: $variant,
                    language: $input->language,
                    name: $card->name,
                    rarity: $rarity,
                    imageUrl: $card->imageUrl,
                ));
            }
        }

        $this->entityManager->flush();
    }

    private function anyVariantExists(PokemonSet $pokemonSet, string $localId, string $language): bool
    {
        return array_any(['normal', 'reverse', 'holo', 'firstEdition', 'wPromo'], fn (string $variant): bool => $this->cardRepository->findByFunctionalIdentity($pokemonSet, $localId, $variant, $language) instanceof Card);
    }

    private function upsertRarity(string $label, string $language): Rarity
    {
        $code = Rarity::slug($label);
        $rarity = $this->rarityRepository->find($code);
        if (!$rarity instanceof Rarity) {
            $rarity = new Rarity($code);
            $this->entityManager->persist($rarity);
        }

        $rarity->upsertTranslation($language, $label);

        return $rarity;
    }
}
