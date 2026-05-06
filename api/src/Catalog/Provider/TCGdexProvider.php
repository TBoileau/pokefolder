<?php

declare(strict_types=1);

namespace App\Catalog\Provider;

use App\Catalog\DTO\TCGdexSet;

/**
 * Read-side abstraction over TCGdex. The application depends on this
 * interface so the synchroniser can be tested against in-memory fixtures
 * without touching the network.
 */
interface TCGdexProvider
{
    /**
     * Returns the set's catalogue snapshot for the given language, or null
     * if the set is not available in that language.
     */
    public function fetchSet(string $setId, string $language): ?TCGdexSet;
}
