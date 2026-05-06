<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use RuntimeException;

use function sprintf;

final class BinderNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $binderId)
    {
        parent::__construct(sprintf('Binder "%s" not found.', $binderId));
    }
}
