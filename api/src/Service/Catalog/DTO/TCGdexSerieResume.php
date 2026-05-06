<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

final readonly class TCGdexSerieResume
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $logo = null,
    ) {
    }
}
