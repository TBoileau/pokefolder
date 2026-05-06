<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Card;
use App\Entity\PokemonSet;
use App\Entity\Rarity;
use App\Entity\Serie;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Helper to spin up a complete Card row (Serie + Set + Rarity + Card)
 * in a single call. Use in tests that need a Card but don't care about
 * the upstream catalog shape.
 */
final class CardFixtureFactory
{
    public static function create(
        EntityManagerInterface $em,
        string $setId = 'base1',
        string $serieId = 'base',
        string $numberInSet = '1',
        string $variant = 'normal',
        string $language = 'en',
        string $name = 'Test card',
        ?string $rarityCode = 'common',
        ?string $imageUrl = null,
    ): Card {
        $serieRepo = $em->getRepository(Serie::class);
        $serie = $serieRepo->find($serieId);
        if (!$serie instanceof Serie) {
            $serie = new Serie($serieId);
            $serie->upsertTranslation('en', $serieId);
            $em->persist($serie);
        }

        $setRepo = $em->getRepository(PokemonSet::class);
        $set = $setRepo->find($setId);
        if (!$set instanceof PokemonSet) {
            $set = new PokemonSet($setId, $serie);
            $set->upsertTranslation('en', $setId);
            $em->persist($set);
        }

        $rarity = null;
        if (null !== $rarityCode) {
            $rarityRepo = $em->getRepository(Rarity::class);
            $rarity = $rarityRepo->find($rarityCode);
            if (!$rarity instanceof Rarity) {
                $rarity = new Rarity($rarityCode);
                $rarity->upsertTranslation('en', $rarityCode);
                $em->persist($rarity);
            }
        }

        $card = new Card(
            pokemonSet: $set,
            numberInSet: $numberInSet,
            variant: $variant,
            language: $language,
            name: $name,
            rarity: $rarity,
            imageUrl: $imageUrl,
        );
        $em->persist($card);

        return $card;
    }
}
