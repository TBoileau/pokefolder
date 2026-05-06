<?php

declare(strict_types=1);

namespace App\Tests\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexSet;
use App\Service\Catalog\Provider\TCGdexProvider;

/**
 * Test double: holds set fixtures keyed by (setId, language) and returns
 * them deterministically. Anything not registered returns null, mirroring
 * the production behaviour for missing sets/languages.
 */
final class InMemoryTCGdexProvider implements TCGdexProvider
{
    /**
     * @var array<string, TCGdexSet>
     */
    private array $sets = [];

    public function register(string $setId, string $language, TCGdexSet $set): void
    {
        $this->sets[self::key($setId, $language)] = $set;
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSet
    {
        return $this->sets[self::key($setId, $language)] ?? null;
    }

    private static function key(string $setId, string $language): string
    {
        return $setId.'|'.$language;
    }
}
