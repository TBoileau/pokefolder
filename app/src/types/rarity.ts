import type { ApiResource } from './api'

export type RarityTranslation = {
  language: string
  name: string
}

export type Rarity = ApiResource & {
  '@type': 'Rarity'
  code: string
  translations: RarityTranslation[]
}

export function pickRarityName(rarity: Rarity, language: string, fallback = 'en'): string {
  return (
    rarity.translations.find((t) => t.language === language)?.name ??
    rarity.translations.find((t) => t.language === fallback)?.name ??
    rarity.code
  )
}
