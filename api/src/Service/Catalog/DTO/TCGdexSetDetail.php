<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

use DateTimeImmutable;

final readonly class TCGdexSetDetail
{
    /**
     * @param list<TCGdexCardResume> $cards
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $serieId,
        public ?string $logo,
        public ?string $symbol,
        public ?DateTimeImmutable $releaseDate,
        public ?int $cardCountTotal,
        public ?int $cardCountOfficial,
        public ?bool $legalStandard,
        public ?bool $legalExpanded,
        public ?string $tcgOnlineId,
        public ?string $abbreviationOfficial,
        public ?string $abbreviationNormal,
        public array $cards,
    ) {
    }
}
