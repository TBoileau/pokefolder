<?php

declare(strict_types=1);

namespace App\Service\Catalog\Provider;

use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexSet;
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

    public function fetchSet(string $setId, string $language): ?TCGdexSet
    {
        $this->tcgdex->lang = $language;

        $set = $this->tcgdex->set->get($setId);
        if (null === $set) {
            return null;
        }

        $cards = [];
        foreach ($set->cards as $resume) {
            $card = $resume->toCard();
            $variants = $this->extractActiveVariants($card->variants);
            if ([] === $variants) {
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

    public function listSetIds(string $language): array
    {
        $this->tcgdex->lang = $language;

        // Hit the raw endpoint rather than $tcgdex->set->list() to sidestep
        // a PHPStan-unfriendly generic in the SDK's Endpoint docblock
        // (template name `List` collides with PHPStan's `list` keyword).
        $response = $this->tcgdex->fetch('sets');
        if (!is_iterable($response)) {
            return [];
        }

        // Each item is a stdClass deserialised from the JSON response.
        // Read it through get_object_vars() so PHPStan can narrow the
        // `id` property's type (object access on stdClass is rejected
        // under bleedingEdge + checkImplicitMixed).
        $ids = [];
        foreach ($response as $item) {
            if (!is_object($item)) {
                continue;
            }

            $vars = get_object_vars($item);
            $id = $vars['id'] ?? null;
            if (is_string($id)) {
                $ids[] = $id;
            }
        }

        return $ids;
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
