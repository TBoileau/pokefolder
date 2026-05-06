/**
 * TCGdex stores card image URLs as bases without extension or quality
 * suffix. To resolve an actual image, append `/<quality>.<format>`.
 *
 * See https://tcgdex.dev/fr/assets.
 */

export type TCGdexImageQuality = 'low' | 'high'
export type TCGdexImageFormat = 'webp' | 'png' | 'jpg'

export function tcgdexImageUrl(
  baseUrl: string,
  quality: TCGdexImageQuality = 'high',
  format: TCGdexImageFormat = 'webp',
): string {
  return `${baseUrl}/${quality}.${format}`
}
