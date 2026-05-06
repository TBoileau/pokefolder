<?php

declare(strict_types=1);

namespace App\Tests\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexSet;
use App\Service\Catalog\Provider\TCGdexProvider;

use function strlen;

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

    public function register(string $setId, string $language, TCGdexSet $tcGdexSet): void
    {
        $this->sets[$this->key($setId, $language)] = $tcGdexSet;
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSet
    {
        return $this->sets[$this->key($setId, $language)] ?? null;
    }

    public function listSetIds(string $language): array
    {
        $ids = [];
        $suffix = '|'.$language;
        foreach (array_keys($this->sets) as $key) {
            if (str_ends_with($key, $suffix)) {
                $ids[] = substr($key, 0, -strlen($suffix));
            }
        }

        return $ids;
    }

    private function key(string $setId, string $language): string
    {
        return $setId.'|'.$language;
    }
}
