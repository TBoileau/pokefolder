import { useMutation } from '@tanstack/react-query'

type DispatchResponse = {
  scope: 'all' | 'set'
  setId?: string
  status: 'dispatched'
}

async function postJson<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    method: 'POST',
    headers: { Accept: 'application/json' },
  })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }
  return (await response.json()) as T
}

export function useSyncAllMutation() {
  return useMutation({
    mutationKey: ['sync', 'all'],
    mutationFn: () => postJson<DispatchResponse>('/api/sync'),
  })
}

export function useSyncSetMutation() {
  return useMutation({
    mutationKey: ['sync', 'set'],
    mutationFn: (setId: string) =>
      postJson<DispatchResponse>(`/api/sync/${encodeURIComponent(setId)}`),
  })
}
