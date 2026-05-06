<?php

declare(strict_types=1);

namespace App\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexSet;

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

    /**
     * Returns every TCGdex set identifier available in the given language.
     *
     * @return list<string>
     */
    public function listSetIds(string $language): array;
}
