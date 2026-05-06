<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only listings of the closed enums (variants, languages) used by
 * the front to populate selects. These are not Doctrine entities; the
 * lists are hardcoded (variants) or pulled from configuration (languages).
 */
final readonly class EnumsController
{
    private const array VARIANTS = [
        ['code' => 'normal', 'label' => 'Normal'],
        ['code' => 'reverse', 'label' => 'Reverse'],
        ['code' => 'holo', 'label' => 'Holo'],
        ['code' => 'firstEdition', 'label' => '1st Edition'],
        ['code' => 'wPromo', 'label' => 'Promo'],
    ];

    private const array LANGUAGE_LABELS = [
        'en' => 'English',
        'fr' => 'Français',
        'ja' => '日本語',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'es' => 'Español',
    ];

    /**
     * @param list<string> $languages
     */
    public function __construct(
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private array $languages,
    ) {
    }

    #[Route(path: '/api/variants', name: 'app_variants', methods: ['GET'])]
    public function variants(): JsonResponse
    {
        return new JsonResponse(self::VARIANTS);
    }

    #[Route(path: '/api/languages', name: 'app_languages', methods: ['GET'])]
    public function languages(): JsonResponse
    {
        $items = [];
        foreach ($this->languages as $language) {
            $items[] = [
                'code' => $language,
                'label' => self::LANGUAGE_LABELS[$language] ?? $language,
            ];
        }

        return new JsonResponse($items);
    }
}
