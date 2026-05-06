<?php

declare(strict_types=1);

namespace App\Catalog;

final class SetNotFoundException extends \RuntimeException
{
    public function __construct(public readonly string $setId)
    {
        parent::__construct(\sprintf('Set "%s" not found in any configured TCGdex language.', $setId));
    }
}
