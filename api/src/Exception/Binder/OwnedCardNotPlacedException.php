<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use App\Entity\OwnedCard;
use RuntimeException;

use function sprintf;

final class OwnedCardNotPlacedException extends RuntimeException
{
    public function __construct(public readonly OwnedCard $ownedCard)
    {
        parent::__construct(sprintf(
            'OwnedCard %s is not placed in any binder slot.',
            $ownedCard->getId()->toRfc4122(),
        ));
    }
}
