<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSet;

final class Output
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
