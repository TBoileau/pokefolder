import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Card } from '@/types/card'
import type { Condition, OwnedCard } from '@/types/ownedCard'

export type CardFilters = {
  serieId?: string | undefined
  setId?: string | undefined
  language?: string | undefined
  variants?: string[] | undefined
  rarities?: string[] | undefined
  search?: string | undefined
}

function buildSearch(filters: CardFilters): string {
  const params = new URLSearchParams()
  if (filters.setId) {
    params.set('pokemonSet', `/api/pokemon_sets/${filters.setId}`)
  } else if (filters.serieId) {
    params.set('pokemonSet.serie', `/api/series/${filters.serieId}`)
  }
  if (filters.language) {
    params.set('language', filters.language)
  }
  for (const v of filters.variants ?? []) {
    params.append('variant[]', v)
  }
  for (const r of filters.rarities ?? []) {
    params.append('rarity[]', `/api/rarities/${r}`)
  }
  if (filters.search && filters.search.trim() !== '') {
    params.set('search', filters.search.trim())
  }
  return params.toString()
}

export function useCardsQuery(filters: CardFilters, enabled = true) {
  const search = buildSearch(filters)
  return useQuery({
    queryKey: ['cards', search],
    enabled,
    queryFn: () => fetcher<HydraCollection<Card>>(`/api/cards?${search}`),
    placeholderData: (previous) => previous,
  })
}

export function useOwnedCardsBySetQuery(setId: string | undefined, language: string | undefined) {
  const enabled = !!setId && !!language
  return useQuery({
    queryKey: ['owned-cards', 'by-set', setId ?? null, language ?? null],
    enabled,
    queryFn: () => {
      const params = new URLSearchParams()
      params.set('itemsPerPage', '500')
      params.set('card.pokemonSet', `/api/pokemon_sets/${setId}`)
      if (language) params.set('card.language', language)
      return fetcher<HydraCollection<OwnedCard>>(`/api/owned_cards?${params.toString()}`)
    },
  })
}

async function postLdJson<T>(url: string, body: unknown): Promise<T> {
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/ld+json', Accept: 'application/ld+json' },
    body: JSON.stringify(body),
  })
  if (!response.ok) throw new Error(`HTTP ${response.status} on ${url}`)
  return (await response.json()) as T
}

async function deleteResource(url: string): Promise<void> {
  const response = await fetch(url, { method: 'DELETE' })
  if (!response.ok && response.status !== 204) throw new Error(`HTTP ${response.status} on ${url}`)
}

export function useAddOwnedCardMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ cardIri, condition }: { cardIri: string; condition: Condition }) =>
      postLdJson<OwnedCard>('/api/owned_cards', { card: cardIri, condition }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['owned-cards'] })
      await queryClient.invalidateQueries({ queryKey: ['collection'] })
    },
  })
}

export function useDeleteOwnedCardSimpleMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (ownedCardId: string) => deleteResource(`/api/owned_cards/${ownedCardId}`),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['owned-cards'] })
      await queryClient.invalidateQueries({ queryKey: ['collection'] })
    },
  })
}
