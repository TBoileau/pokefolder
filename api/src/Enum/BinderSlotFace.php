<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Which side of a binder page a slot belongs to. For non double-sided
 * binders, only Recto is valid; see PositionOutOfBoundsException.
 */
enum BinderSlotFace: string
{
    case Recto = 'recto';
    case Verso = 'verso';
}
