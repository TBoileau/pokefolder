import { useQuery } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Card } from '@/types/card'

export const CARDS_PER_PAGE = 24

export function useCardsQuery(page: number) {
  return useQuery({
    queryKey: ['cards', { page, itemsPerPage: CARDS_PER_PAGE }],
    queryFn: () =>
      fetcher<HydraCollection<Card>>(`/api/cards?page=${page}&itemsPerPage=${CARDS_PER_PAGE}`),
    placeholderData: (previous) => previous,
  })
}
