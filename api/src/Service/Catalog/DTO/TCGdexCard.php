<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

/**
 * Snapshot of a single Pokémon TCG card as returned by TCGdex, with the
 * variants flattened into the list of identifiers the card is actually
 * available in (e.g. ['normal', 'holo']).
 */
final readonly class TCGdexCard
{
    /**
     * @param list<string> $activeVariants
     */
    public function __construct(
        public string $localId,
        public string $name,
        public string $rarity,
        public ?string $imageUrl,
        public array $activeVariants,
    ) {
    }
}
