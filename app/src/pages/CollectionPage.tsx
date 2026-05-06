import { Link, useNavigate, useSearch } from '@tanstack/react-router'
import { ChevronLeft, ChevronRight, Library, Plus, Search, X } from 'lucide-react'
import { useEffect, useState } from 'react'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { COLLECTION_PER_PAGE, useCollectionQuery } from '@/hooks/useCollectionQuery'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { CollectionEntry } from '@/types/collection'

const LANGUAGES = [
  { value: 'fr', label: 'Français' },
  { value: 'en', label: 'English' },
] as const

const VARIANTS = [
  { value: 'normal', label: 'Normal' },
  { value: 'reverse', label: 'Reverse' },
  { value: 'holo', label: 'Holo' },
  { value: 'firstEdition', label: '1st Edition' },
  { value: 'wPromo', label: 'Promo' },
] as const

const CONDITIONS = [
  { value: 'M', label: 'Mint' },
  { value: 'NM', label: 'Near Mint' },
  { value: 'EX', label: 'Excellent' },
  { value: 'GD', label: 'Good' },
  { value: 'LP', label: 'Light Played' },
  { value: 'PL', label: 'Played' },
  { value: 'HP', label: 'Heavy Played' },
  { value: 'DMG', label: 'Damaged' },
] as const

const ALL_VALUE = '__all__'

export function CollectionPage() {
  const search = useSearch({ from: '/collection' })
  const navigate = useNavigate({ from: '/collection' })

  const { data, isLoading, isError, error, isPlaceholderData } = useCollectionQuery(search.page, {
    q: search.q,
    setId: search.setId,
    language: search.language,
    variant: search.variant,
    condition: search.condition,
  })

  const totalItems = data?.totalItems ?? 0
  const totalPages = Math.max(1, Math.ceil(totalItems / COLLECTION_PER_PAGE))
  const entries = data?.member ?? []
  const hasActiveFilters =
    Boolean(search.q) ||
    Boolean(search.setId) ||
    Boolean(search.language) ||
    Boolean(search.variant) ||
    Boolean(search.condition)

  const updateSearch = (next: Partial<typeof search>) => {
    void navigate({
      search: (prev) => ({ ...prev, ...next, page: next.page ?? 1 }),
    })
  }

  return (
    <div className="flex min-h-svh flex-col">
      <header className="border-b">
        <div className="mx-auto flex max-w-6xl items-center gap-3 px-6 py-4">
          <Link to="/" className="flex items-center gap-3 text-foreground">
            <Library className="size-6 text-primary" />
            <h1 className="font-semibold text-lg tracking-tight">pokefolder</h1>
          </Link>
        </div>
      </header>

      <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-6 py-10">
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-1">
            <h2 className="font-semibold text-2xl tracking-tight">Ma collection</h2>
            <p className="text-muted-foreground text-sm">
              {totalItems > 0
                ? `${totalItems} cartes différentes ${hasActiveFilters ? 'correspondent aux filtres' : 'dans la collection'}.`
                : 'Aucune carte pour l’instant.'}
            </p>
          </div>
          <Button asChild>
            <Link to="/collection/add">
              <Plus />
              Ajouter une carte
            </Link>
          </Button>
        </div>

        <FiltersBar search={search} hasActiveFilters={hasActiveFilters} onChange={updateSearch} />

        {isError ? (
          <ErrorState message={(error as Error).message} />
        ) : isLoading ? (
          <CollectionGridSkeleton />
        ) : entries.length === 0 ? (
          <EmptyState hasActiveFilters={hasActiveFilters} />
        ) : (
          <div
            className={isPlaceholderData ? 'opacity-60 transition-opacity' : 'transition-opacity'}
          >
            <CollectionGrid entries={entries} />
          </div>
        )}

        {totalPages > 1 && (
          <PaginationBar
            page={search.page}
            totalPages={totalPages}
            disabled={isLoading || isError}
            onPrev={() => updateSearch({ page: search.page - 1 })}
            onNext={() => updateSearch({ page: search.page + 1 })}
          />
        )}
      </main>
    </div>
  )
}

type CollectionSearch = {
  q?: string | undefined
  setId?: string | undefined
  language?: 'fr' | 'en' | undefined
  variant?: 'normal' | 'reverse' | 'holo' | 'firstEdition' | 'wPromo' | undefined
  condition?: 'M' | 'NM' | 'EX' | 'GD' | 'LP' | 'PL' | 'HP' | 'DMG' | undefined
}

function FiltersBar({
  search,
  hasActiveFilters,
  onChange,
}: {
  search: CollectionSearch
  hasActiveFilters: boolean
  onChange: (next: CollectionSearch) => void
}) {
  const [qInput, setQInput] = useState(search.q ?? '')
  const [setInput, setSetInput] = useState(search.setId ?? '')

  useEffect(() => {
    setQInput(search.q ?? '')
  }, [search.q])
  useEffect(() => {
    setSetInput(search.setId ?? '')
  }, [search.setId])

  useEffect(() => {
    const trimmed = qInput.trim()
    if (trimmed === (search.q ?? '')) return
    const id = window.setTimeout(() => {
      onChange({ q: trimmed === '' ? undefined : trimmed })
    }, 300)
    return () => window.clearTimeout(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qInput, onChange, search.q])
  useEffect(() => {
    const trimmed = setInput.trim()
    if (trimmed === (search.setId ?? '')) return
    const id = window.setTimeout(() => {
      onChange({ setId: trimmed === '' ? undefined : trimmed })
    }, 300)
    return () => window.clearTimeout(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [setInput, search.setId, onChange])

  const reset = () => {
    setQInput('')
    setSetInput('')
    onChange({
      q: undefined,
      setId: undefined,
      language: undefined,
      variant: undefined,
      condition: undefined,
    })
  }

  return (
    <section
      aria-label="Filtres"
      className="grid grid-cols-1 gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5"
    >
      <div className="relative flex flex-col gap-1 lg:col-span-2">
        <label htmlFor="collection-filter-q" className="font-medium text-muted-foreground text-xs">
          Recherche
        </label>
        <span className="relative">
          <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            id="collection-filter-q"
            value={qInput}
            onChange={(event) => setQInput(event.target.value)}
            placeholder="Nom de carte"
            className="pl-8"
          />
        </span>
      </div>

      <div className="flex flex-col gap-1">
        <label
          htmlFor="collection-filter-set"
          className="font-medium text-muted-foreground text-xs"
        >
          Set
        </label>
        <Input
          id="collection-filter-set"
          value={setInput}
          onChange={(event) => setSetInput(event.target.value)}
          placeholder="ex. base1"
        />
      </div>

      <div className="flex flex-col gap-1">
        <span id="collection-filter-language" className="font-medium text-muted-foreground text-xs">
          Langue
        </span>
        <Select
          value={search.language ?? ALL_VALUE}
          onValueChange={(value) =>
            onChange({ language: value === ALL_VALUE ? undefined : (value as 'fr' | 'en') })
          }
        >
          <SelectTrigger aria-labelledby="collection-filter-language">
            <SelectValue placeholder="Toutes" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL_VALUE}>Toutes</SelectItem>
            {LANGUAGES.map((language) => (
              <SelectItem key={language.value} value={language.value}>
                {language.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="flex flex-col gap-1">
        <span id="collection-filter-variant" className="font-medium text-muted-foreground text-xs">
          Variant
        </span>
        <Select
          value={search.variant ?? ALL_VALUE}
          onValueChange={(value) =>
            onChange({
              variant:
                value === ALL_VALUE
                  ? undefined
                  : (value as 'normal' | 'reverse' | 'holo' | 'firstEdition' | 'wPromo'),
            })
          }
        >
          <SelectTrigger aria-labelledby="collection-filter-variant">
            <SelectValue placeholder="Tous" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL_VALUE}>Tous</SelectItem>
            {VARIANTS.map((variant) => (
              <SelectItem key={variant.value} value={variant.value}>
                {variant.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="flex flex-col gap-1">
        <span
          id="collection-filter-condition"
          className="font-medium text-muted-foreground text-xs"
        >
          Condition
        </span>
        <Select
          value={search.condition ?? ALL_VALUE}
          onValueChange={(value) =>
            onChange({
              condition:
                value === ALL_VALUE
                  ? undefined
                  : (value as 'M' | 'NM' | 'EX' | 'GD' | 'LP' | 'PL' | 'HP' | 'DMG'),
            })
          }
        >
          <SelectTrigger aria-labelledby="collection-filter-condition">
            <SelectValue placeholder="Toutes" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL_VALUE}>Toutes</SelectItem>
            {CONDITIONS.map((condition) => (
              <SelectItem key={condition.value} value={condition.value}>
                {condition.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {hasActiveFilters && (
        <div className="flex items-end sm:col-span-2 lg:col-span-5 lg:justify-end">
          <Button variant="ghost" size="sm" onClick={reset}>
            <X />
            Réinitialiser les filtres
          </Button>
        </div>
      )}
    </section>
  )
}

function CollectionGrid({ entries }: { entries: CollectionEntry[] }) {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      {entries.map((entry) => (
        <li key={entry.card.id}>
          <Link
            to="/collection/cards/$cardId"
            params={{ cardId: entry.card.id }}
            className="block focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
          >
            <UICard className="h-full overflow-hidden transition-shadow hover:shadow-md">
              <div className="relative aspect-[5/7] w-full bg-muted">
                {entry.card.imageUrl ? (
                  <img
                    src={tcgdexImageUrl(entry.card.imageUrl, 'high', 'webp')}
                    alt={entry.card.name}
                    loading="lazy"
                    className="h-full w-full object-contain"
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center text-muted-foreground text-xs">
                    no image
                  </div>
                )}
                <span className="absolute top-2 right-2 inline-flex min-w-7 items-center justify-center rounded-full bg-primary px-2 py-0.5 font-semibold text-primary-foreground text-xs shadow">
                  ×{entry.totalQuantity}
                </span>
              </div>
              <CardHeader className="gap-1 p-4">
                <CardTitle className="line-clamp-1">{entry.card.name}</CardTitle>
                <CardDescription>
                  {entry.card.setId} · #{entry.card.numberInSet}
                </CardDescription>
              </CardHeader>
              <CardContent className="flex flex-wrap gap-1 p-4 pt-0">
                {Object.entries(entry.byCondition).map(([condition, count]) => (
                  <Tag key={condition}>
                    {condition} ×{count}
                  </Tag>
                ))}
              </CardContent>
            </UICard>
          </Link>
        </li>
      ))}
    </ul>
  )
}

function Tag({ children }: { children: React.ReactNode }) {
  return (
    <span className="inline-flex items-center rounded-md bg-muted px-2 py-0.5 font-medium text-muted-foreground text-xs">
      {children}
    </span>
  )
}

function CollectionGridSkeleton() {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      {Array.from({ length: COLLECTION_PER_PAGE }, (_unused, i) => i).map((idx) => (
        <li key={idx}>
          <UICard className="h-full overflow-hidden">
            <Skeleton className="aspect-[5/7] w-full rounded-none" />
            <div className="space-y-2 p-4">
              <Skeleton className="h-4 w-3/4" />
              <Skeleton className="h-3 w-1/2" />
              <div className="flex gap-1 pt-2">
                <Skeleton className="h-5 w-12" />
                <Skeleton className="h-5 w-10" />
              </div>
            </div>
          </UICard>
        </li>
      ))}
    </ul>
  )
}

function EmptyState({ hasActiveFilters }: { hasActiveFilters: boolean }) {
  if (hasActiveFilters) {
    return (
      <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
        <p className="font-medium">Aucune carte de la collection ne correspond à ces filtres.</p>
      </div>
    )
  }

  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
      <p className="font-medium">La collection est vide.</p>
      <p className="text-muted-foreground text-sm">
        Ajoute ta première carte une fois la slice "ajout" disponible (#13).
      </p>
    </div>
  )
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="flex flex-col gap-2 rounded-xl border border-destructive/40 bg-destructive/5 p-6">
      <p className="font-medium text-destructive">Impossible de charger la collection.</p>
      <p className="text-sm">{message}</p>
    </div>
  )
}

function PaginationBar({
  page,
  totalPages,
  disabled,
  onPrev,
  onNext,
}: {
  page: number
  totalPages: number
  disabled: boolean
  onPrev: () => void
  onNext: () => void
}) {
  return (
    <nav aria-label="Pagination" className="flex items-center justify-between gap-3 border-t pt-4">
      <p className="text-muted-foreground text-sm">
        Page <span className="font-medium text-foreground">{page}</span> sur {totalPages}
      </p>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={disabled || page <= 1}
          onClick={onPrev}
          aria-label="Page précédente"
        >
          <ChevronLeft />
          Précédent
        </Button>
        <Button
          variant="outline"
          size="sm"
          disabled={disabled || page >= totalPages}
          onClick={onNext}
          aria-label="Page suivante"
        >
          Suivant
          <ChevronRight />
        </Button>
      </div>
    </nav>
  )
}
