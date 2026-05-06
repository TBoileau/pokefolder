<?php

declare(strict_types=1);

namespace App\UseCase\Binder\UnplaceCard;

final readonly class Input
{
    public function __construct(public string $ownedCardId)
    {
    }
}
