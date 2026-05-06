<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

final readonly class Output
{
    public function __construct(public int $dispatched)
    {
    }
}
