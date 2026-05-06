import type { ApiResource } from './api'

export type Card = ApiResource & {
  '@type': 'Card'
  id: string
  setId: string
  numberInSet: string
  variant: string
  language: string
  name: string
  rarity: string
  imageUrl: string | null
}
