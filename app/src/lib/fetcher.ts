/**
 * Minimal fetch wrapper that asks for Hydra (JSON-LD) responses and throws
 * on non-2xx so TanStack Query lifts the error into its `error` state.
 */
export async function fetcher<T>(url: string, init?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    ...init,
    headers: {
      Accept: 'application/ld+json',
      ...init?.headers,
    },
  })

  if (!response.ok) {
    throw new Error(`HTTP ${response.status} on ${url}`)
  }

  return (await response.json()) as T
}
