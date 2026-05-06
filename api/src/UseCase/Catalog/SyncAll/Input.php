<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

final readonly class Input
{
    public function __construct(public bool $force = false)
    {
    }
}
