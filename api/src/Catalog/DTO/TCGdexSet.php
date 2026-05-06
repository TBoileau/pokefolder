<?php

declare(strict_types=1);

namespace App\Catalog\DTO;

final class TCGdexSet
{
    /**
     * @param list<TCGdexCard> $cards
     */
    public function __construct(
        public readonly string $id,
        public readonly array $cards,
    ) {
    }
}
