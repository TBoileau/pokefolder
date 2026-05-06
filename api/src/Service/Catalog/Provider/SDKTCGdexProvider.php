<?php

declare(strict_types=1);

namespace App\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexCardResume;
use App\Service\Catalog\DTO\TCGdexSerieDetail;
use App\Service\Catalog\DTO\TCGdexSerieResume;
use App\Service\Catalog\DTO\TCGdexSetDetail;
use App\Service\Catalog\DTO\TCGdexSetResume;
use DateTimeImmutable;
use TCGdex\Model\SubModel\Variants;
use TCGdex\TCGdex;

use function get_object_vars;
use function is_object;
use function is_string;

/**
 * Production implementation of TCGdexProvider backed by the TCGdex PHP
 * SDK. The TCGdex client is autowired (assembled by TCGdexClientFactory
 * declared in services.yaml). Per-call language is set on the SDK's
 * mutable `lang` property — safe in this context because messages are
 * processed sequentially by the worker.
 */
final readonly class SDKTCGdexProvider implements TCGdexProvider
{
    public function __construct(private TCGdex $tcgdex)
    {
    }

    public function listSeries(string $language): array
    {
        $this->tcgdex->lang = $language;

        $response = $this->tcgdex->fetch('series');
        if (!is_iterable($response)) {
            return [];
        }

        $series = [];
        foreach ($response as $item) {
            if (!is_object($item)) {
                continue;
            }

            $vars = get_object_vars($item);
            $id = $vars['id'] ?? null;
            $name = $vars['name'] ?? null;
            $logo = $vars['logo'] ?? null;
            if (is_string($id) && is_string($name)) {
                $series[] = new TCGdexSerieResume(
                    id: $id,
                    name: $name,
                    logo: is_string($logo) ? $logo : null,
                );
            }
        }

        return $series;
    }

    public function fetchSerie(string $serieId, string $language): ?TCGdexSerieDetail
    {
        $this->tcgdex->lang = $language;

        $serie = $this->tcgdex->serie->get($serieId);
        if (null === $serie) {
            return null;
        }

        $sets = [];
        foreach ($serie->sets as $resume) {
            $sets[] = new TCGdexSetResume(
                id: $resume->id,
                name: $resume->name,
                logo: $resume->logo,
                symbol: $resume->symbol,
            );
        }

        $releaseDate = '' !== $serie->releaseDate
            ? new DateTimeImmutable($serie->releaseDate)
            : null;

        return new TCGdexSerieDetail(
            id: $serie->id,
            name: $serie->name,
            logo: $serie->logo,
            releaseDate: $releaseDate,
            sets: $sets,
        );
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSetDetail
    {
        $this->tcgdex->lang = $language;

        $set = $this->tcgdex->set->get($setId);
        if (null === $set) {
            return null;
        }

        $cards = [];
        foreach ($set->cards as $resume) {
            $cards[] = new TCGdexCardResume(
                id: $resume->id,
                localId: $resume->localId,
                name: $resume->name,
            );
        }

        $releaseDate = '' !== $set->releaseDate
            ? new DateTimeImmutable($set->releaseDate)
            : null;

        return new TCGdexSetDetail(
            id: $set->id,
            name: $set->name,
            serieId: $set->serie->id,
            logo: $set->logo,
            symbol: $set->symbol,
            releaseDate: $releaseDate,
            cardCountTotal: $set->cardCount->total,
            cardCountOfficial: $set->cardCount->official,
            legalStandard: $set->legal->standard,
            legalExpanded: $set->legal->expanded,
            tcgOnlineId: $set->tcgOnline,
            abbreviationOfficial: $set->abbreviation->official ?? null,
            abbreviationNormal: null,
            cards: $cards,
        );
    }

    public function fetchCard(string $setId, string $localId, string $language): ?TCGdexCard
    {
        $this->tcgdex->lang = $language;

        $card = $this->tcgdex->card->get($setId.'-'.$localId);
        if (null === $card) {
            return null;
        }

        return new TCGdexCard(
            id: $card->id,
            localId: $card->localId,
            name: $card->name,
            rarity: '' !== $card->rarity ? $card->rarity : null,
            imageUrl: $card->image,
            activeVariants: $this->extractActiveVariants($card->variants),
        );
    }

    /**
     * @return list<string>
     */
    private function extractActiveVariants(Variants $variants): array
    {
        $active = [];
        foreach (['normal', 'reverse', 'holo', 'firstEdition', 'wPromo'] as $variant) {
            if (true === $variants->{$variant}) {
                $active[] = $variant;
            }
        }

        return $active;
    }
}
