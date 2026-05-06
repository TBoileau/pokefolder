<?php

declare(strict_types=1);

namespace App\Exception\Binder;

use App\Entity\Binder;
use App\Service\Binder\BinderSlotPosition;
use RuntimeException;

use function sprintf;

final class SlotAlreadyOccupiedException extends RuntimeException
{
    public function __construct(
        public readonly Binder $binder,
        public readonly BinderSlotPosition $position,
    ) {
        parent::__construct(sprintf(
            'Slot at page %d %s row %d col %d is already occupied in binder %s.',
            $position->pageNumber,
            $position->face->value,
            $position->row,
            $position->col,
            $binder->getId()->toRfc4122(),
        ));
    }
}
