<?php

declare(strict_types=1);

namespace App\Tests\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexSerieDetail;
use App\Service\Catalog\DTO\TCGdexSerieResume;
use App\Service\Catalog\DTO\TCGdexSetDetail;
use App\Service\Catalog\Provider\TCGdexProvider;

/**
 * In-memory test double for TCGdexProvider. Tests pre-load fixtures via
 * the register* methods, then run handlers without touching the network.
 *
 * @internal
 */
final class InMemoryTCGdexProvider implements TCGdexProvider
{
    /**
     * @var array<string, list<TCGdexSerieResume>>
     */
    private array $seriesByLanguage = [];

    /**
     * @var array<string, TCGdexSerieDetail>
     */
    private array $serieDetails = [];

    /**
     * @var array<string, TCGdexSetDetail>
     */
    private array $setDetails = [];

    /**
     * @var array<string, TCGdexCard>
     */
    private array $cardDetails = [];

    /**
     * @param list<TCGdexSerieResume> $series
     */
    public function registerSeriesList(string $language, array $series): void
    {
        $this->seriesByLanguage[$language] = $series;
    }

    public function registerSerie(string $serieId, string $language, TCGdexSerieDetail $detail): void
    {
        $this->serieDetails[$this->key($serieId, $language)] = $detail;
    }

    public function registerSet(string $setId, string $language, TCGdexSetDetail $detail): void
    {
        $this->setDetails[$this->key($setId, $language)] = $detail;
    }

    public function registerCard(string $setId, string $localId, string $language, TCGdexCard $card): void
    {
        $this->cardDetails[$this->cardKey($setId, $localId, $language)] = $card;
    }

    public function listSeries(string $language): array
    {
        return $this->seriesByLanguage[$language] ?? [];
    }

    public function fetchSerie(string $serieId, string $language): ?TCGdexSerieDetail
    {
        return $this->serieDetails[$this->key($serieId, $language)] ?? null;
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSetDetail
    {
        return $this->setDetails[$this->key($setId, $language)] ?? null;
    }

    public function fetchCard(string $setId, string $localId, string $language): ?TCGdexCard
    {
        return $this->cardDetails[$this->cardKey($setId, $localId, $language)] ?? null;
    }

    private function key(string $id, string $language): string
    {
        return $id.'|'.$language;
    }

    private function cardKey(string $setId, string $localId, string $language): string
    {
        return $setId.'|'.$localId.'|'.$language;
    }
}
