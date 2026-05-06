<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSets;

final readonly class Input
{
    public function __construct(
        public string $serieId,
        public string $language,
        public bool $force = false,
    ) {
    }
}
