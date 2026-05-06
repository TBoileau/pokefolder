<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

final readonly class TCGdexSet
{
    /**
     * @param list<TCGdexCard> $cards
     */
    public function __construct(
        public string $id,
        public array $cards,
    ) {
    }
}
