<?php

declare(strict_types=1);

namespace App\Service\Binder;

use App\Enum\BinderSlotFace;

/**
 * Value object that addresses a single physical slot in a binder, by
 * (pageNumber, face, row, col). Coordinates are 1-indexed.
 */
final readonly class BinderSlotPosition
{
    public function __construct(
        public int $pageNumber,
        public BinderSlotFace $face,
        public int $row,
        public int $col,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->pageNumber === $other->pageNumber
            && $this->face === $other->face
            && $this->row === $other->row
            && $this->col === $other->col;
    }
}
