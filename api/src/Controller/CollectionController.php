<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\Condition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Aggregated read view of the user's collection: each Card the user owns
 * is returned once with its total physical-copy count and a per-condition
 * breakdown. Driven by query params (q / setId / language / variant /
 * condition) so the same UI can filter the catalog and the collection
 * with the same vocabulary.
 */
final readonly class CollectionController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/api/collection', name: 'app_collection_get', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(100, max(1, $request->query->getInt('itemsPerPage', 24)));

        $q = trim($request->query->getString('q'));
        $setId = trim($request->query->getString('setId'));
        $language = trim($request->query->getString('language'));
        $variant = trim($request->query->getString('variant'));
        $conditionParam = trim($request->query->getString('condition'));
        $condition = '' === $conditionParam ? null : Condition::tryFrom($conditionParam);

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c')
            ->innerJoin(OwnedCard::class, 'oc', 'WITH', 'oc.card = c')
            ->groupBy('c.id')
            ->orderBy('c.setId', 'ASC')
            ->addOrderBy('c.numberInSet', 'ASC');

        $totalQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT c.id)')
            ->from(Card::class, 'c')
            ->innerJoin(OwnedCard::class, 'oc', 'WITH', 'oc.card = c');

        foreach ([$queryBuilder, $totalQb] as $qb) {
            if ('' !== $q) {
                $qb->andWhere('LOWER(c.name) LIKE LOWER(:q) OR LOWER(c.numberInSet) LIKE LOWER(:q)')
                    ->setParameter('q', '%'.$q.'%');
            }

            if ('' !== $setId) {
                $qb->andWhere('c.setId = :setId')->setParameter('setId', $setId);
            }

            if ('' !== $language) {
                $qb->andWhere('c.language = :language')->setParameter('language', $language);
            }

            if ('' !== $variant) {
                $qb->andWhere('c.variant = :variant')->setParameter('variant', $variant);
            }

            if (null !== $condition) {
                $qb->andWhere('oc.condition = :condition')->setParameter('condition', $condition->value);
            }
        }

        $queryBuilder->setFirstResult(($page - 1) * $itemsPerPage)->setMaxResults($itemsPerPage);

        /** @var list<Card> $cards */
        $cards = $queryBuilder->getQuery()->getResult();
        $totalItems = (int) $totalQb->getQuery()->getSingleScalarResult();

        $cardIds = array_map(static fn (Card $card): string => $card->getId()->toRfc4122(), $cards);

        $countsByCard = [];
        if ([] !== $cardIds) {
            $countsRows = $this->entityManager->createQueryBuilder()
                ->select('IDENTITY(oc.card) AS cardId', 'oc.condition AS condition', 'COUNT(oc.id) AS cnt')
                ->from(OwnedCard::class, 'oc')
                ->where('IDENTITY(oc.card) IN (:ids)')
                ->setParameter('ids', $cardIds)
                ->groupBy('oc.card', 'oc.condition')
                ->getQuery()
                ->getArrayResult();

            /** @var list<array{cardId: string, condition: string|Condition, cnt: int|string}> $countsRows */
            foreach ($countsRows as $countRow) {
                $cardId = $countRow['cardId'];
                $rawCondition = $countRow['condition'];
                $conditionValue = $rawCondition instanceof Condition
                    ? $rawCondition->value
                    : $rawCondition;
                $countsByCard[$cardId][$conditionValue] = (int) $countRow['cnt'];
            }
        }

        $members = [];
        foreach ($cards as $card) {
            $cardId = $card->getId()->toRfc4122();
            $byCondition = $countsByCard[$cardId] ?? [];
            $totalQuantity = array_sum($byCondition);
            $members[] = [
                '@id' => '/api/cards/'.$cardId,
                '@type' => 'CollectionEntry',
                'card' => [
                    '@id' => '/api/cards/'.$cardId,
                    'id' => $cardId,
                    'setId' => $card->getSetId(),
                    'numberInSet' => $card->getNumberInSet(),
                    'variant' => $card->getVariant(),
                    'language' => $card->getLanguage(),
                    'name' => $card->getName(),
                    'rarity' => $card->getRarity(),
                    'imageUrl' => $card->getImageUrl(),
                ],
                'totalQuantity' => $totalQuantity,
                'byCondition' => $byCondition,
            ];
        }

        return new JsonResponse([
            '@context' => '/api/contexts/Collection',
            '@id' => '/api/collection',
            '@type' => 'Collection',
            'totalItems' => $totalItems,
            'member' => $members,
        ]);
    }
}
