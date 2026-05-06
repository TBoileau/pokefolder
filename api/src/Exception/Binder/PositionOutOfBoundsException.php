<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use App\Entity\Binder;
use App\Service\Binder\BinderSlotPosition;
use RuntimeException;

use function sprintf;

final class PositionOutOfBoundsException extends RuntimeException
{
    public function __construct(
        public readonly Binder $binder,
        public readonly BinderSlotPosition $position,
        string $reason,
    ) {
        parent::__construct(sprintf(
            'Position page %d %s row %d col %d is out of bounds for binder %s: %s',
            $position->pageNumber,
            $position->face->value,
            $position->row,
            $position->col,
            $binder->getId()->toRfc4122(),
            $reason,
        ));
    }
}
