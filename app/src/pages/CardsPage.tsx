import { Link, useNavigate, useSearch } from '@tanstack/react-router'
import { CheckSquare, Loader2, Search, Square, X } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { CardContent, Card as UICard } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import {
  type CardFilters,
  useAddOwnedCardMutation,
  useCardsQuery,
  useDeleteOwnedCardSimpleMutation,
  useOwnedCardsBySetQuery,
} from '@/hooks/useCardsQuery'
import {
  useLanguagesQuery,
  usePokemonSetsQuery,
  useRaritiesQuery,
  useSeriesQuery,
  useVariantsQuery,
} from '@/hooks/useCatalogReferences'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { CardsSearch } from '@/router'
import type { Card } from '@/types/card'
import type { OwnedCard } from '@/types/ownedCard'
import { pickSetName } from '@/types/pokemonSet'
import { pickRarityName } from '@/types/rarity'
import { pickSerieName } from '@/types/serie'

export function CardsPage() {
  const search = useSearch({ from: '/cards' })
  const navigate = useNavigate({ from: '/cards' })

  const seriesQuery = useSeriesQuery()
  const setsQuery = usePokemonSetsQuery(search.serie)
  const raritiesQuery = useRaritiesQuery(search.serie, search.set)
  const variantsQuery = useVariantsQuery()
  const languagesQuery = useLanguagesQuery()

  const [searchInput, setSearchInput] = useState(search.q ?? '')
  useEffect(() => setSearchInput(search.q ?? ''), [search.q])
  useEffect(() => {
    const id = window.setTimeout(() => {
      const trimmed = searchInput.trim()
      if (trimmed === (search.q ?? '')) return
      void navigate({
        search: (prev) => ({ ...prev, q: trimmed === '' ? undefined : trimmed }),
      })
    }, 300)
    return () => window.clearTimeout(id)
  }, [searchInput, search.q, navigate])

  // When the available rarity list shrinks (serie/set change), drop any
  // selected rarity that is no longer in scope so the cards query doesn't
  // silently filter to nothing.
  const availableRarities = raritiesQuery.data?.member
  useEffect(() => {
    if (!availableRarities) return
    const selected = search.rarities ?? []
    if (selected.length === 0) return
    const allowed = new Set(availableRarities.map((r) => r.code))
    const next = selected.filter((c) => allowed.has(c))
    if (next.length === selected.length) return
    void navigate({
      search: (prev) => ({ ...prev, rarities: next.length === 0 ? undefined : next }),
    })
  }, [availableRarities, search.rarities, navigate])

  const filtersComplete = !!search.serie && !!search.set
  const filters: CardFilters = {
    serieId: search.serie,
    setId: search.set,
    language: search.language,
    variants: search.variants,
    rarities: search.rarities,
    search: search.q,
  }

  const cardsQuery = useCardsQuery(filters, filtersComplete)
  const ownedQuery = useOwnedCardsBySetQuery(search.set, search.language)

  const allCards = cardsQuery.data?.member ?? []
  const ownedCards = ownedQuery.data?.member ?? []

  const masterOn = !!search.master

  const ownedByCardId = useMemo(() => {
    const map = new Map<string, OwnedCard[]>()
    for (const oc of ownedCards) {
      const arr = map.get(oc.card.id) ?? []
      arr.push(oc)
      map.set(oc.card.id, arr)
    }
    return map
  }, [ownedCards])

  const displayCards = useMemo(() => {
    if (masterOn) return allCards.map((c) => ({ representative: c, variants: [c] }))
    // Aggregate by (numberInSet, language) — keep one row, list variants.
    const groups = new Map<string, { representative: Card; variants: Card[] }>()
    for (const card of allCards) {
      const key = `${card.numberInSet}|${card.language}`
      const existing = groups.get(key)
      if (existing) {
        existing.variants.push(card)
        if (card.variant === 'normal') existing.representative = card
      } else {
        groups.set(key, { representative: card, variants: [card] })
      }
    }
    return [...groups.values()]
  }, [allCards, masterOn])

  const updateSearch = (next: Partial<typeof search>) => {
    void navigate({ search: (prev) => ({ ...prev, ...next }) })
  }

  const [bulkMode, setBulkMode] = useState(false)
  const [bulkSelection, setBulkSelection] = useState<Set<string>>(new Set())
  useEffect(() => {
    if (!bulkMode) setBulkSelection(new Set())
  }, [bulkMode])

  const addOwned = useAddOwnedCardMutation()

  const bulkAdd = () => {
    const ids = [...bulkSelection]
    if (ids.length === 0) return
    let done = 0
    let errors = 0
    for (const cardIri of ids) {
      addOwned.mutate(
        { cardIri, condition: 'NM' },
        {
          onSuccess: () => {
            done += 1
            if (done + errors === ids.length) {
              toast.success(`${done} carte${done > 1 ? 's' : ''} ajoutée${done > 1 ? 's' : ''}`)
              setBulkSelection(new Set())
              setBulkMode(false)
            }
          },
          onError: () => {
            errors += 1
            if (done + errors === ids.length) {
              toast.error(`${errors} échec${errors > 1 ? 's' : ''} sur ${ids.length}`)
            }
          },
        },
      )
    }
  }

  return (
    <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-6 py-10">
      <div className="space-y-1">
        <h2 className="font-semibold text-2xl tracking-tight">Catalogue Pokémon TCG</h2>
        <p className="text-muted-foreground text-sm">
          Choisis une série puis un set pour explorer les cartes. Active "Master Set" pour voir
          chaque variant individuellement.
        </p>
      </div>

      <FiltersBar
        search={search}
        onChange={updateSearch}
        seriesQuery={seriesQuery}
        setsQuery={setsQuery}
        raritiesQuery={raritiesQuery}
        variantsQuery={variantsQuery}
        languagesQuery={languagesQuery}
        searchInput={searchInput}
        onSearchInputChange={setSearchInput}
      />

      {!filtersComplete ? (
        <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
          <p className="font-medium">Sélectionne une série et un set pour afficher les cartes.</p>
          <p className="text-muted-foreground text-sm">
            La liste devient navigable une fois ces deux filtres choisis.
          </p>
        </div>
      ) : cardsQuery.isError ? (
        <ErrorState message={(cardsQuery.error as Error).message} />
      ) : (
        <>
          {masterOn ? (
            <BulkBar
              bulkMode={bulkMode}
              selectionCount={bulkSelection.size}
              onToggleBulk={() => setBulkMode((v) => !v)}
              onBulkAdd={bulkAdd}
              onClearSelection={() => setBulkSelection(new Set())}
              isPending={addOwned.isPending}
            />
          ) : null}

          {cardsQuery.isLoading ? (
            <CardsGridSkeleton />
          ) : (
            <CardsGrid
              displayCards={displayCards}
              ownedByCardId={ownedByCardId}
              masterOn={masterOn}
              bulkMode={bulkMode}
              bulkSelection={bulkSelection}
              onToggleBulkSelection={(cardIri) => {
                setBulkSelection((prev) => {
                  const next = new Set(prev)
                  if (next.has(cardIri)) next.delete(cardIri)
                  else next.add(cardIri)
                  return next
                })
              }}
            />
          )}
        </>
      )}
    </main>
  )
}

function FiltersBar({
  search,
  onChange,
  seriesQuery,
  setsQuery,
  raritiesQuery,
  variantsQuery,
  languagesQuery,
  searchInput,
  onSearchInputChange,
}: {
  search: CardsSearch
  onChange: (next: Partial<CardsSearch>) => void
  seriesQuery: ReturnType<typeof useSeriesQuery>
  setsQuery: ReturnType<typeof usePokemonSetsQuery>
  raritiesQuery: ReturnType<typeof useRaritiesQuery>
  variantsQuery: ReturnType<typeof useVariantsQuery>
  languagesQuery: ReturnType<typeof useLanguagesQuery>
  searchInput: string
  onSearchInputChange: (value: string) => void
}) {
  const series = seriesQuery.data?.member ?? []
  const sets = setsQuery.data?.member ?? []
  const rarities = raritiesQuery.data?.member ?? []
  const variants = variantsQuery.data ?? []
  const languages = languagesQuery.data ?? []
  const lang = search.language ?? 'en'

  const toggleVariant = (code: string) => {
    const current = new Set(search.variants ?? [])
    if (current.has(code)) current.delete(code)
    else current.add(code)
    onChange({ variants: [...current] })
  }

  const toggleRarity = (code: string) => {
    const current = new Set(search.rarities ?? [])
    if (current.has(code)) current.delete(code)
    else current.add(code)
    onChange({ rarities: [...current] })
  }

  return (
    <section className="flex flex-col gap-4 rounded-xl border bg-card p-4">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div className="flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Série</span>
          <Select
            value={search.serie ?? ''}
            onValueChange={(value) =>
              onChange({ serie: value === '' ? undefined : value, set: undefined })
            }
          >
            <SelectTrigger>
              <SelectValue placeholder="Choisir une série" />
            </SelectTrigger>
            <SelectContent>
              {series.map((s) => (
                <SelectItem key={s.id} value={s.id}>
                  {pickSerieName(s, lang)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Set</span>
          <Select
            value={search.set ?? ''}
            onValueChange={(value) => onChange({ set: value === '' ? undefined : value })}
            disabled={!search.serie}
          >
            <SelectTrigger>
              <SelectValue placeholder={search.serie ? 'Choisir un set' : 'Choisir une série'} />
            </SelectTrigger>
            <SelectContent>
              {sets.map((s) => (
                <SelectItem key={s.id} value={s.id}>
                  {pickSetName(s, lang)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Langue</span>
          <Select
            value={search.language ?? ''}
            onValueChange={(value) => onChange({ language: value === '' ? undefined : value })}
          >
            <SelectTrigger>
              <SelectValue placeholder="Toutes" />
            </SelectTrigger>
            <SelectContent>
              {languages.map((l) => (
                <SelectItem key={l.code} value={l.code}>
                  {l.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="relative flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Recherche</span>
          <span className="relative">
            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={searchInput}
              onChange={(event) => onSearchInputChange(event.target.value)}
              placeholder="Nom, numéro, set, série…"
              className="pl-8"
            />
          </span>
        </div>
      </div>

      <div className="flex flex-wrap gap-4">
        <div className="flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Variants</span>
          <div className="flex flex-wrap gap-1">
            {variants.map((v) => {
              const active = (search.variants ?? []).includes(v.code)
              return (
                <button
                  type="button"
                  key={v.code}
                  onClick={() => toggleVariant(v.code)}
                  className={`rounded-full border px-2.5 py-0.5 text-xs transition-colors ${
                    active
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-border bg-background text-muted-foreground hover:bg-accent'
                  }`}
                >
                  {v.label}
                </button>
              )
            })}
          </div>
        </div>
        <div className="flex flex-col gap-1">
          <span className="font-medium text-muted-foreground text-xs">Raretés</span>
          <div className="flex max-w-xl flex-wrap gap-1">
            {rarities.map((r) => {
              const active = (search.rarities ?? []).includes(r.code)
              return (
                <button
                  type="button"
                  key={r.code}
                  onClick={() => toggleRarity(r.code)}
                  className={`rounded-full border px-2.5 py-0.5 text-xs transition-colors ${
                    active
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-border bg-background text-muted-foreground hover:bg-accent'
                  }`}
                >
                  {pickRarityName(r, lang)}
                </button>
              )
            })}
          </div>
        </div>
        <label className="ml-auto flex items-center gap-2 self-end text-sm">
          <input
            type="checkbox"
            checked={!!search.master}
            onChange={(event) => onChange({ master: event.target.checked || undefined })}
            className="size-4"
          />
          <span>Master Set (un row par variant + cocher pour ajouter)</span>
        </label>
      </div>
    </section>
  )
}

function BulkBar({
  bulkMode,
  selectionCount,
  onToggleBulk,
  onBulkAdd,
  onClearSelection,
  isPending,
}: {
  bulkMode: boolean
  selectionCount: number
  onToggleBulk: () => void
  onBulkAdd: () => void
  onClearSelection: () => void
  isPending: boolean
}) {
  return (
    <div className="flex flex-wrap items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-sm">
      <Button variant={bulkMode ? 'default' : 'outline'} size="sm" onClick={onToggleBulk}>
        {bulkMode ? <CheckSquare className="size-4" /> : <Square className="size-4" />}
        {bulkMode ? 'Mode bulk actif' : 'Activer bulk select'}
      </Button>
      {bulkMode ? (
        <>
          <span className="text-muted-foreground text-xs">
            {selectionCount} carte{selectionCount > 1 ? 's' : ''} sélectionnée
            {selectionCount > 1 ? 's' : ''}
          </span>
          <Button size="sm" disabled={selectionCount === 0 || isPending} onClick={onBulkAdd}>
            {isPending ? <Loader2 className="size-4 animate-spin" /> : null}
            Ajouter à la collection (NM)
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={onClearSelection}
            disabled={selectionCount === 0}
          >
            <X className="size-4" />
            Vider la sélection
          </Button>
        </>
      ) : null}
    </div>
  )
}

function CardsGrid({
  displayCards,
  ownedByCardId,
  masterOn,
  bulkMode,
  bulkSelection,
  onToggleBulkSelection,
}: {
  displayCards: { representative: Card; variants: Card[] }[]
  ownedByCardId: Map<string, OwnedCard[]>
  masterOn: boolean
  bulkMode: boolean
  bulkSelection: Set<string>
  onToggleBulkSelection: (cardIri: string) => void
}) {
  if (displayCards.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
        <p className="font-medium">Aucune carte ne correspond aux filtres.</p>
      </div>
    )
  }

  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
      {displayCards.map(({ representative, variants }) => (
        <li key={`${representative.id}-${variants.length}`}>
          <CatalogCardCell
            representative={representative}
            variants={variants}
            ownedByCardId={ownedByCardId}
            masterOn={masterOn}
            bulkMode={bulkMode}
            bulkChecked={bulkSelection.has(representative['@id'])}
            onToggleBulk={onToggleBulkSelection}
          />
        </li>
      ))}
    </ul>
  )
}

function CatalogCardCell({
  representative,
  variants,
  ownedByCardId,
  masterOn,
  bulkMode,
  bulkChecked,
  onToggleBulk,
}: {
  representative: Card
  variants: Card[]
  ownedByCardId: Map<string, OwnedCard[]>
  masterOn: boolean
  bulkMode: boolean
  bulkChecked: boolean
  onToggleBulk: (cardIri: string) => void
}) {
  const owned = ownedByCardId.get(representative.id) ?? []
  const ownedCount = owned.length
  const sameCondition = owned.every((oc) => oc.condition === 'NM')
  const isIndeterminate = ownedCount > 1 || (ownedCount === 1 && !sameCondition)

  const addOwned = useAddOwnedCardMutation()
  const deleteOwned = useDeleteOwnedCardSimpleMutation()

  const handleCheckbox = () => {
    if (ownedCount === 0) {
      addOwned.mutate(
        { cardIri: representative['@id'], condition: 'NM' },
        {
          onSuccess: () => toast.success('Carte ajoutée'),
          onError: (e) => toast.error((e as Error).message),
        },
      )
    } else if (ownedCount === 1 && sameCondition && owned[0]) {
      const target = owned[0]
      deleteOwned.mutate(target.id, {
        onSuccess: () => toast.success('Carte retirée'),
        onError: (e) => toast.error((e as Error).message),
      })
    }
  }

  const checkboxLabel =
    ownedCount === 0
      ? 'Ajouter à la collection'
      : isIndeterminate
        ? `${ownedCount} exemplaires — voir détail`
        : 'Retirer de la collection'

  return (
    <UICard className="flex h-full flex-col overflow-hidden">
      <div className="relative aspect-[5/7] bg-muted">
        {representative.imageUrl ? (
          <img
            src={tcgdexImageUrl(representative.imageUrl, 'low')}
            alt={representative.name}
            className="size-full object-cover"
            loading="lazy"
          />
        ) : (
          <div className="flex size-full items-center justify-center text-center text-muted-foreground text-xs">
            {representative.name}
          </div>
        )}
        {!masterOn && variants.length > 1 ? (
          <span className="absolute top-1 right-1 rounded-full bg-background/90 px-1.5 py-0.5 font-medium text-[10px] uppercase shadow-sm">
            ×{variants.length} variants
          </span>
        ) : null}
      </div>
      <CardContent className="flex flex-1 flex-col gap-2 p-3">
        <div className="space-y-0.5">
          <p className="line-clamp-1 font-medium text-sm">{representative.name}</p>
          <p className="text-muted-foreground text-xs">
            #{representative.numberInSet} · {representative.variant} · {representative.language}
          </p>
        </div>
        {masterOn ? (
          <div className="mt-auto flex items-center gap-2">
            {bulkMode ? (
              <label className="flex items-center gap-2 text-xs">
                <input
                  type="checkbox"
                  checked={bulkChecked}
                  onChange={() => onToggleBulk(representative['@id'])}
                  className="size-4"
                />
                <span>Sélectionner</span>
              </label>
            ) : isIndeterminate ? (
              <Link
                to="/collection/cards/$cardId"
                params={{ cardId: representative.id }}
                className="text-primary text-xs underline"
                title={checkboxLabel}
              >
                ×{ownedCount} possédées — voir
              </Link>
            ) : (
              <label className="flex items-center gap-2 text-xs" title={checkboxLabel}>
                <input
                  type="checkbox"
                  checked={ownedCount === 1}
                  onChange={handleCheckbox}
                  disabled={addOwned.isPending || deleteOwned.isPending}
                  className="size-4"
                />
                <span>{ownedCount === 1 ? 'Possédée' : 'Ajouter (NM)'}</span>
              </label>
            )}
          </div>
        ) : null}
      </CardContent>
    </UICard>
  )
}

function CardsGridSkeleton() {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
      {Array.from({ length: 12 }, (_unused, i) => i).map((idx) => (
        <li key={idx}>
          <Skeleton className="aspect-[5/7] w-full" />
        </li>
      ))}
    </ul>
  )
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="rounded-md border border-destructive/40 bg-destructive/5 p-4 text-destructive text-sm">
      {message}
    </div>
  )
}
