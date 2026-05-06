<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

/**
 * Snapshot of a single Pokémon TCG card as returned by TCGdex, with the
 * variants flattened into the list of identifiers the card is actually
 * available in (e.g. ['normal', 'holo']).
 */
final class TCGdexCard
{
    /**
     * @param list<string> $activeVariants
     */
    public function __construct(
        public readonly string $localId,
        public readonly string $name,
        public readonly string $rarity,
        public readonly ?string $imageUrl,
        public readonly array $activeVariants,
    ) {
    }
}
