<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use App\Entity\OwnedCard;
use RuntimeException;

use function sprintf;

final class OwnedCardAlreadyPlacedException extends RuntimeException
{
    public function __construct(public readonly OwnedCard $ownedCard)
    {
        parent::__construct(sprintf(
            'OwnedCard %s is already placed in a binder slot.',
            $ownedCard->getId()->toRfc4122(),
        ));
    }
}
