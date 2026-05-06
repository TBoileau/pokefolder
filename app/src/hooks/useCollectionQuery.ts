import { useQuery } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { CollectionEntry } from '@/types/collection'

export const COLLECTION_PER_PAGE = 24

export type CollectionFilters = {
  q?: string | undefined
  setId?: string | undefined
  language?: string | undefined
  variant?: string | undefined
  condition?: string | undefined
}

function buildSearch(page: number, filters: CollectionFilters): string {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('itemsPerPage', String(COLLECTION_PER_PAGE))
  for (const key of ['q', 'setId', 'language', 'variant', 'condition'] as const) {
    const value = filters[key]
    if (value && value.trim() !== '') {
      params.set(key, value.trim())
    }
  }
  return params.toString()
}

export function useCollectionQuery(page: number, filters: CollectionFilters = {}) {
  const search = buildSearch(page, filters)
  return useQuery({
    queryKey: ['collection', search],
    queryFn: () => fetcher<HydraCollection<CollectionEntry>>(`/api/collection?${search}`),
    placeholderData: (previous) => previous,
  })
}
