import type { ApiResource } from './api'
import type { Serie } from './serie'

export type SetTranslation = {
  language: string
  name: string
  abbreviationOfficial: string | null
  abbreviationNormal: string | null
}

export type PokemonSet = ApiResource & {
  '@type': 'PokemonSet'
  id: string
  serie: Serie | string
  logo: string | null
  symbol: string | null
  releaseDate: string | null
  cardCountTotal: number | null
  cardCountOfficial: number | null
  legalStandard: boolean | null
  legalExpanded: boolean | null
  tcgOnlineId: string | null
  translations: SetTranslation[]
}

export function pickSetName(set: PokemonSet, language: string, fallback = 'en'): string {
  return (
    set.translations.find((t) => t.language === language)?.name ??
    set.translations.find((t) => t.language === fallback)?.name ??
    set.id
  )
}
