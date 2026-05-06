<?php

declare(strict_types=1);

namespace App\Service\Catalog\DTO;

final readonly class TCGdexCardResume
{
    public function __construct(
        public string $id,
        public string $localId,
        public string $name,
    ) {
    }
}
