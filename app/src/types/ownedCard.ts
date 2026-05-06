import type { ApiResource } from './api'
import type { Card } from './card'

export type Condition = 'M' | 'NM' | 'EX' | 'GD' | 'LP' | 'PL' | 'HP' | 'DMG'

export type OwnedCard = ApiResource & {
  '@type': 'OwnedCard'
  id: string
  card: Card
  condition: Condition
  createdAt: string
  updatedAt: string
}
