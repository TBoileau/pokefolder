import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { fetcher } from '@/lib/fetcher'
import type { HydraCollection } from '@/types/api'
import type { Binder } from '@/types/binder'
import type { BinderSlot } from '@/types/binderSlot'

export function useBindersQuery() {
  return useQuery({
    queryKey: ['binders', 'list'],
    queryFn: () => fetcher<HydraCollection<Binder>>('/api/binders?itemsPerPage=100'),
  })
}

export function useBinderQuery(binderId: string) {
  return useQuery({
    queryKey: ['binders', 'item', binderId],
    queryFn: () => fetcher<Binder>(`/api/binders/${binderId}`),
  })
}

export function useBinderSlotsQuery(binderId: string) {
  return useQuery({
    queryKey: ['binders', 'slots', binderId],
    queryFn: () =>
      fetcher<HydraCollection<BinderSlot>>(
        `/api/binder_slots?binder=/api/binders/${binderId}&itemsPerPage=500`,
      ),
  })
}

export type BinderInput = {
  name: string
  description: string | null
  pageCount: number
  cols: number
  rows: number
  doubleSided: boolean
}

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

async function patchMergeJson<T>(url: string, body: unknown): Promise<T> {
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

async function deleteResource(url: string): Promise<void> {
  const response = await fetch(url, { method: 'DELETE' })
  if (!response.ok && response.status !== 204) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }
}

export function useCreateBinderMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: BinderInput) => postLdJson<Binder>('/api/binders', input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['binders'] })
    },
  })
}

export function useUpdateBinderMutation(binderId: string) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: Partial<BinderInput>) =>
      patchMergeJson<Binder>(`/api/binders/${binderId}`, input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['binders'] })
    },
  })
}

export function useDeleteBinderMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (binderId: string) => deleteResource(`/api/binders/${binderId}`),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['binders'] })
    },
  })
}
