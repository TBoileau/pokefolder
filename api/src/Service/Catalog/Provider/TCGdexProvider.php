<?php

declare(strict_types=1);

namespace App\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexSerieDetail;
use App\Service\Catalog\DTO\TCGdexSerieResume;
use App\Service\Catalog\DTO\TCGdexSetDetail;

/**
 * Read-side abstraction over TCGdex. Production impl uses the SDK; tests
 * can pass an in-memory fake without touching the network.
 */
interface TCGdexProvider
{
    /**
     * Returns the list of all series available in the given language.
     *
     * @return list<TCGdexSerieResume>
     */
    public function listSeries(string $language): array;

    /**
     * Returns the full serie (with its sets list) in the given language,
     * or null if the serie is not available.
     */
    public function fetchSerie(string $serieId, string $language): ?TCGdexSerieDetail;

    /**
     * Returns the full set (with its cards listing) in the given language,
     * or null if the set is not available.
     */
    public function fetchSet(string $setId, string $language): ?TCGdexSetDetail;

    /**
     * Returns the full card (variants, rarity, image) in the given language,
     * or null if the card is not available.
     */
    public function fetchCard(string $setId, string $localId, string $language): ?TCGdexCard;
}
