<?php

declare(strict_types=1);

namespace App\UseCase\Binder\SuggestPlacement;

final readonly class Output
{
    public function __construct(public ?string $binderId)
    {
    }
}
