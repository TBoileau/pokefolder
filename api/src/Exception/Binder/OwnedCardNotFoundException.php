<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use RuntimeException;

use function sprintf;

final class OwnedCardNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $ownedCardId)
    {
        parent::__construct(sprintf('OwnedCard "%s" not found.', $ownedCardId));
    }
}
