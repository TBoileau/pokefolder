<?php

declare(strict_types=1);

namespace App\UseCase\Binder\MoveCard;

use App\Enum\BinderSlotFace;

final readonly class Input
{
    public function __construct(
        public string $ownedCardId,
        public string $binderId,
        public int $pageNumber,
        public BinderSlotFace $face,
        public int $row,
        public int $col,
    ) {
    }
}
