<?php

declare(strict_types=1);

namespace App\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexSet;
use TCGdex\Model\SubModel\Variants;
use TCGdex\TCGdex;

/**
 * Production implementation of TCGdexProvider backed by the TCGdex PHP
 * SDK. The TCGdex client is autowired (assembled by TCGdexClientFactory
 * declared in services.yaml). Per-call language is set on the SDK's
 * mutable `lang` property — safe in this context because messages are
 * processed sequentially by the worker.
 */
final class SDKTCGdexProvider implements TCGdexProvider
{
    public function __construct(private readonly TCGdex $tcgdex)
    {
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSet
    {
        $this->tcgdex->lang = $language;

        $set = $this->tcgdex->set->get($setId);
        if ($set === null) {
            return null;
        }

        $cards = [];
        foreach ($set->cards as $resume) {
            $card = $resume->toCard();
            if ($card === null) {
                continue;
            }
            $variants = self::extractActiveVariants($card->variants);
            if ($variants === []) {
                continue;
            }
            $cards[] = new TCGdexCard(
                localId: $card->localId,
                name: $card->name,
                rarity: $card->rarity,
                imageUrl: $card->image,
                activeVariants: $variants,
            );
        }

        return new TCGdexSet(id: $set->id, cards: $cards);
    }

    /**
     * @return list<string>
     */
    private static function extractActiveVariants(Variants $variants): array
    {
        $active = [];
        foreach (['normal', 'reverse', 'holo', 'firstEdition', 'wPromo'] as $variant) {
            if ($variants->{$variant} === true) {
                $active[] = $variant;
            }
        }

        return $active;
    }
}
