import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Card } from '@/types/card'
import type { Condition, OwnedCard } from '@/types/ownedCard'

export function useCardQuery(cardId: string) {
  return useQuery({
    queryKey: ['cards', 'item', cardId],
    queryFn: () => fetcher<Card>(`/api/cards/${cardId}`),
  })
}

export function useOwnedCardsByCardQuery(cardId: string) {
  return useQuery({
    queryKey: ['owned-cards', { card: cardId }],
    queryFn: () =>
      fetcher<HydraCollection<OwnedCard>>(
        `/api/owned_cards?card=${encodeURIComponent(`/api/cards/${cardId}`)}&itemsPerPage=100&order%5BcreatedAt%5D=DESC`,
      ),
  })
}

async function patchJson<T>(url: string, body: unknown): Promise<T> {
  const response = await fetch(url, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/merge-patch+json',
      Accept: 'application/ld+json',
    },
    body: JSON.stringify(body),
  })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }
  return (await response.json()) as T
}

export function useUpdateConditionMutation(cardId: string) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ ownedCardId, condition }: { ownedCardId: string; condition: Condition }) =>
      patchJson<OwnedCard>(`/api/owned_cards/${ownedCardId}`, { condition }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['owned-cards', { card: cardId }] })
      await queryClient.invalidateQueries({ queryKey: ['collection'] })
    },
  })
}

async function deleteResource(url: string): Promise<void> {
  const response = await fetch(url, { method: 'DELETE' })
  if (!response.ok && response.status !== 204) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }
}

export function useDeleteOwnedCardMutation(cardId: string) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (ownedCardId: string) => deleteResource(`/api/owned_cards/${ownedCardId}`),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['owned-cards', { card: cardId }] })
      await queryClient.invalidateQueries({ queryKey: ['collection'] })
    },
  })
}
