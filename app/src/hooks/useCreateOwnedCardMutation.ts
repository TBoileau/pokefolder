import { useMutation, useQueryClient } from '@tanstack/react-query'

import type { Condition, OwnedCard } from '@/types/ownedCard'

async function postLdJson<T>(url: string, body: unknown): Promise<T> {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      Accept: 'application/ld+json',
    },
    body: JSON.stringify(body),
  })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }
  return (await response.json()) as T
}

export function useCreateOwnedCardMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ cardIri, condition }: { cardIri: string; condition: Condition }) =>
      postLdJson<OwnedCard>('/api/owned_cards', { card: cardIri, condition }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['collection'] })
      await queryClient.invalidateQueries({ queryKey: ['owned-cards'] })
    },
  })
}
