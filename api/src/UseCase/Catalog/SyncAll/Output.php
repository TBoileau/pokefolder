<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

final class Output
{
    public function __construct(public readonly int $dispatched)
    {
    }
}
