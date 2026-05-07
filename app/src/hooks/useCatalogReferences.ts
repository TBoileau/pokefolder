import { useQuery } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { PokemonSet } from '@/types/pokemonSet'
import type { Rarity } from '@/types/rarity'
import type { Serie } from '@/types/serie'

export type EnumOption = { code: string; label: string }

export function useSeriesQuery() {
  return useQuery({
    queryKey: ['catalog', 'series'],
    queryFn: () => fetcher<HydraCollection<Serie>>('/api/series?itemsPerPage=200'),
  })
}

export function usePokemonSetsQuery(serieId: string | undefined) {
  const enabled = !!serieId
  return useQuery({
    queryKey: ['catalog', 'sets', serieId ?? null],
    enabled,
    queryFn: () =>
      fetcher<HydraCollection<PokemonSet>>(
        `/api/pokemon_sets?itemsPerPage=200&serie=${encodeURIComponent(`/api/series/${serieId}`)}`,
      ),
  })
}

export function useRaritiesQuery(serieId?: string, setId?: string) {
  const params = new URLSearchParams()
  params.set('itemsPerPage', '200')
  if (setId) {
    params.set('pokemonSet', `/api/pokemon_sets/${setId}`)
  } else if (serieId) {
    params.set('serie', `/api/series/${serieId}`)
  }
  return useQuery({
    queryKey: ['catalog', 'rarities', serieId ?? null, setId ?? null],
    queryFn: () => fetcher<HydraCollection<Rarity>>(`/api/rarities?${params.toString()}`),
  })
}

export function useVariantsQuery() {
  return useQuery({
    queryKey: ['catalog', 'variants'],
    queryFn: () => fetcher<EnumOption[]>('/api/variants'),
  })
}

export function useLanguagesQuery() {
  return useQuery({
    queryKey: ['catalog', 'languages'],
    queryFn: () => fetcher<EnumOption[]>('/api/languages'),
  })
}
