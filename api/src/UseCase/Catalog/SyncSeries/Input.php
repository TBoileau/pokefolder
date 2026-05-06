<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSeries;

final readonly class Input
{
    public function __construct(
        public string $language,
        public bool $force = false,
    ) {
    }
}
