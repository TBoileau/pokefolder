import type { ApiResource } from './api'

export type Binder = ApiResource & {
  '@type': 'Binder'
  id: string
  name: string
  description: string | null
  pageCount: number
  cols: number
  rows: number
  doubleSided: boolean
  capacity: number
  createdAt: string
  updatedAt: string
}
