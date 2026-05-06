/**
 * Hydra/JSON-LD collection envelope as returned by API Platform 4.x.
 */
export type HydraCollection<T> = {
  '@context': string
  '@id': string
  '@type': 'Collection'
  totalItems: number
  member: T[]
}

export type ApiResource = {
  '@id': string
  '@type': string
}
