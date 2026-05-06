<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

/**
 * Full snapshot of a single TCGdex card (after `tcgdex.card.get(...)`).
 */
final readonly class TCGdexCard
{
    /**
     * @param list<string> $activeVariants
     */
    public function __construct(
        public string $id,
        public string $localId,
        public string $name,
        public ?string $rarity,
        public ?string $imageUrl,
        public array $activeVariants,
    ) {
    }
}
