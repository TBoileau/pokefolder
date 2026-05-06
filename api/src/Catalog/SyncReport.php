<?php

declare(strict_types=1);

namespace App\Catalog;

final class SyncReport
{
    public function __construct(
        public readonly string $setId,
        public int $created = 0,
        public int $updated = 0,
        public int $unchanged = 0,
    ) {
    }

    public function processed(): int
    {
        return $this->created + $this->updated + $this->unchanged;
    }
}
