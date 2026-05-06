import { useQuery } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Card } from '@/types/card'

export const CARDS_PER_PAGE = 24

export type CardFilters = {
  name?: string
  setId?: string
  language?: string
  variant?: string
}

function buildSearch(page: number, filters: CardFilters): string {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('itemsPerPage', String(CARDS_PER_PAGE))
  if (filters.name && filters.name.trim() !== '') {
    params.set('name', filters.name.trim())
  }
  if (filters.setId && filters.setId.trim() !== '') {
    params.set('setId', filters.setId.trim())
  }
  if (filters.language) {
    params.set('language', filters.language)
  }
  if (filters.variant) {
    params.set('variant', filters.variant)
  }
  return params.toString()
}

export function useCardsQuery(page: number, filters: CardFilters = {}) {
  const search = buildSearch(page, filters)
  return useQuery({
    queryKey: ['cards', search],
    queryFn: () => fetcher<HydraCollection<Card>>(`/api/cards?${search}`),
    placeholderData: (previous) => previous,
  })
}
