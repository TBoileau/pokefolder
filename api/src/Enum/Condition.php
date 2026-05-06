<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Physical state of an OwnedCard. Closed enum, English standard
 * (M / NM / EX / GD / LP / PL / HP / DMG). See CONTEXT.md.
 */
enum Condition: string
{
    case Mint = 'M';
    case NearMint = 'NM';
    case Excellent = 'EX';
    case Good = 'GD';
    case LightPlayed = 'LP';
    case Played = 'PL';
    case HeavyPlayed = 'HP';
    case Damaged = 'DMG';
}
