import { useQuery } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Card } from '@/types/card'

export function useCardSearchQuery(query: string) {
  const trimmed = query.trim()
  return useQuery({
    queryKey: ['cards', 'search', trimmed],
    queryFn: () =>
      fetcher<HydraCollection<Card>>(
        `/api/cards?name=${encodeURIComponent(trimmed)}&itemsPerPage=8`,
      ),
    enabled: trimmed.length >= 2,
    staleTime: 30_000,
  })
}
