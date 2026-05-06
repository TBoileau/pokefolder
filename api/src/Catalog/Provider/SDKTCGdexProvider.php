<?php

declare(strict_types=1);

namespace App\Catalog\Provider;

use App\Catalog\DTO\TCGdexCard;
use App\Catalog\DTO\TCGdexSet;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpClient\Psr18Client;
use TCGdex\Model\SubModel\Variants;
use TCGdex\TCGdex;

/**
 * Production implementation backed by the TCGdex PHP SDK. Wires the SDK's
 * static PSR slots to Symfony components on first use so the SDK uses our
 * HTTP client, cache and PSR-17 factories instead of its built-in Buzz +
 * in-memory cache.
 */
final class SDKTCGdexProvider implements TCGdexProvider
{
    public function __construct(
        ClientInterface $httpClient = new Psr18Client(),
        CacheInterface $cache = new \Symfony\Component\Cache\Psr16Cache(
            new \Symfony\Component\Cache\Adapter\ArrayAdapter(),
        ),
        RequestFactoryInterface $requestFactory = new Psr17Factory(),
        ResponseFactoryInterface $responseFactory = new Psr17Factory(),
    ) {
        TCGdex::$client = $httpClient;
        TCGdex::$cache = $cache;
        TCGdex::$requestFactory = $requestFactory;
        TCGdex::$responseFactory = $responseFactory;
    }

    public function fetchSet(string $setId, string $language): ?TCGdexSet
    {
        $tcgdex = new TCGdex($language);
        $set = $tcgdex->set->get($setId);
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
