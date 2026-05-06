<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncCards;

final readonly class Input
{
    public function __construct(
        public string $setId,
        public string $language,
        public bool $force = false,
    ) {
    }
}
