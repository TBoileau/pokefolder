<?php

declare(strict_types=1);

namespace App\Exception\Catalog;

use RuntimeException;

use function sprintf;

final class SerieNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $serieId)
    {
        parent::__construct(sprintf('Serie "%s" not found in any configured TCGdex language.', $serieId));
    }
}
