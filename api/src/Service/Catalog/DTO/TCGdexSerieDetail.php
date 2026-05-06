<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

use DateTimeImmutable;

final readonly class TCGdexSerieDetail
{
    /**
     * @param list<TCGdexSetResume> $sets
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $logo,
        public ?DateTimeImmutable $releaseDate,
        public array $sets,
    ) {
    }
}
