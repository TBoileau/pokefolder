<?php

declare(strict_types=1);

namespace App\UseCase\Binder\PlaceCard;

use App\Enum\BinderSlotFace;

final readonly class Input
{
    public function __construct(
        public string $binderId,
        public string $ownedCardId,
        public int $pageNumber,
        public BinderSlotFace $face,
        public int $row,
        public int $col,
    ) {
    }
}
