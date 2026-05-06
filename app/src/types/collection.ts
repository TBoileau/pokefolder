import type { ApiResource } from './api'
import type { Card } from './card'

export type CollectionEntry = ApiResource & {
  '@type': 'CollectionEntry'
  card: Card
  totalQuantity: number
  byCondition: Record<string, number>
}
