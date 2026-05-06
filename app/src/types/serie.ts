import type { ApiResource } from './api'

export type SerieTranslation = {
  language: string
  name: string
}

export type Serie = ApiResource & {
  '@type': 'Serie'
  id: string
  logo: string | null
  releaseDate: string | null
  translations: SerieTranslation[]
}

export function pickSerieName(serie: Serie, language: string, fallback = 'en'): string {
  return (
    serie.translations.find((t) => t.language === language)?.name ??
    serie.translations.find((t) => t.language === fallback)?.name ??
    serie.id
  )
}
