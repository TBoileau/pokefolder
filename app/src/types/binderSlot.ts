import type { ApiResource } from './api'
import type { Card } from './card'
import type { Condition } from './ownedCard'

export type BinderSlotFace = 'recto' | 'verso'

export type BinderSlot = ApiResource & {
  '@type': 'BinderSlot'
  id: string
  pageNumber: number
  face: BinderSlotFace
  row: number
  col: number
  ownedCard: {
    '@type': 'OwnedCard'
    id: string
    condition: Condition
    card: Card
  } | null
}
