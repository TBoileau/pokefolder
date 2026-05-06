import { Link, useNavigate } from '@tanstack/react-router'
import { ArrowLeft, CheckCircle2, Library, Loader2, Search } from 'lucide-react'
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
import { useCardSearchQuery } from '@/hooks/useCardSearchQuery'
import { useCreateOwnedCardMutation } from '@/hooks/useCreateOwnedCardMutation'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { Card } from '@/types/card'
import type { Condition } from '@/types/ownedCard'

const CONDITIONS: { value: Condition; label: string }[] = [
  { value: 'M', label: 'Mint (M)' },
  { value: 'NM', label: 'Near Mint (NM)' },
  { value: 'EX', label: 'Excellent (EX)' },
  { value: 'GD', label: 'Good (GD)' },
  { value: 'LP', label: 'Light Played (LP)' },
  { value: 'PL', label: 'Played (PL)' },
  { value: 'HP', label: 'Heavy Played (HP)' },
  { value: 'DMG', label: 'Damaged (DMG)' },
]

export function CollectionAddPage() {
  const navigate = useNavigate({ from: '/collection/add' })
  const [searchInput, setSearchInput] = useState('')
  const [debouncedQuery, setDebouncedQuery] = useState('')
  const [selectedCard, setSelectedCard] = useState<Card | null>(null)
  const [condition, setCondition] = useState<Condition>('NM')
  const createOwned = useCreateOwnedCardMutation()

  useEffect(() => {
    if (selectedCard !== null) return
    const id = window.setTimeout(() => setDebouncedQuery(searchInput), 250)
    return () => window.clearTimeout(id)
  }, [searchInput, selectedCard])

  const search = useCardSearchQuery(debouncedQuery)
  const showSuggestions = selectedCard === null && debouncedQuery.trim().length >= 2

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (selectedCard === null) return
    createOwned.mutate(
      { cardIri: selectedCard['@id'], condition },
      {
        onSuccess: () => {
          void navigate({
            to: '/collection/cards/$cardId',
            params: { cardId: selectedCard.id },
          })
        },
      },
    )
  }

  const reset = () => {
    setSelectedCard(null)
    setSearchInput('')
    setDebouncedQuery('')
  }

  return (
    <div className="flex min-h-svh flex-col">
      <header className="border-b">
        <div className="mx-auto flex max-w-3xl items-center gap-3 px-6 py-4">
          <Link to="/" className="flex items-center gap-3 text-foreground">
            <Library className="size-6 text-primary" />
            <h1 className="font-semibold text-lg tracking-tight">pokefolder</h1>
          </Link>
        </div>
      </header>

      <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-6 py-10">
        <Button variant="ghost" size="sm" asChild className="self-start">
          <Link to="/collection" search={{ page: 1 }}>
            <ArrowLeft />
            Retour à la collection
          </Link>
        </Button>

        <UICard>
          <CardHeader>
            <CardTitle>Ajouter une carte à la collection</CardTitle>
            <CardDescription>
              Cherche la carte dans le catalogue, choisis sa condition, valide.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              {selectedCard === null ? (
                <div className="flex flex-col gap-2">
                  <label
                    htmlFor="add-card-search"
                    className="font-medium text-muted-foreground text-xs"
                  >
                    Carte du catalogue
                  </label>
                  <span className="relative">
                    <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      id="add-card-search"
                      value={searchInput}
                      onChange={(event) => setSearchInput(event.target.value)}
                      placeholder="Tape au moins 2 caractères (nom de carte)…"
                      autoComplete="off"
                      className="pl-8"
                    />
                  </span>
                  {showSuggestions ? (
                    <SuggestionsList
                      query={debouncedQuery}
                      isLoading={search.isLoading || search.isFetching}
                      cards={search.data?.member ?? []}
                      onPick={(card) => setSelectedCard(card)}
                    />
                  ) : (
                    <p className="text-muted-foreground text-xs">
                      Le catalogue doit avoir été synchronisé pour qu'une carte apparaisse.
                    </p>
                  )}
                </div>
              ) : (
                <SelectedCard card={selectedCard} onReset={reset} />
              )}

              <div className="flex flex-col gap-2">
                <span id="add-card-condition" className="font-medium text-muted-foreground text-xs">
                  Condition
                </span>
                <Select
                  value={condition}
                  onValueChange={(value) => setCondition(value as Condition)}
                >
                  <SelectTrigger aria-labelledby="add-card-condition">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {CONDITIONS.map((c) => (
                      <SelectItem key={c.value} value={c.value}>
                        {c.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-center gap-3">
                <Button type="submit" disabled={selectedCard === null || createOwned.isPending}>
                  {createOwned.isPending ? (
                    <>
                      <Loader2 className="animate-spin" />
                      Ajout en cours…
                    </>
                  ) : (
                    <>
                      <CheckCircle2 />
                      Ajouter à la collection
                    </>
                  )}
                </Button>
                {createOwned.isError ? (
                  <span className="text-destructive text-sm">
                    {(createOwned.error as Error).message}
                  </span>
                ) : null}
              </div>
            </form>
          </CardContent>
        </UICard>
      </main>
    </div>
  )
}

function SuggestionsList({
  query,
  isLoading,
  cards,
  onPick,
}: {
  query: string
  isLoading: boolean
  cards: Card[]
  onPick: (card: Card) => void
}) {
  if (isLoading) {
    return <p className="text-muted-foreground text-sm">Recherche…</p>
  }
  if (cards.length === 0) {
    return (
      <p className="text-muted-foreground text-sm">Aucune carte ne correspond à « {query} ».</p>
    )
  }
  return (
    <ul className="flex flex-col divide-y rounded-md border">
      {cards.map((card) => (
        <li key={card.id}>
          <button
            type="button"
            onClick={() => onPick(card)}
            className="flex w-full items-center gap-3 p-3 text-left transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
          >
            <span className="aspect-[5/7] w-10 shrink-0 overflow-hidden rounded bg-muted">
              {card.imageUrl ? (
                <img
                  src={tcgdexImageUrl(card.imageUrl, 'low', 'webp')}
                  alt=""
                  className="h-full w-full object-contain"
                />
              ) : null}
            </span>
            <span className="flex flex-col">
              <span className="font-medium text-sm">{card.name}</span>
              <span className="text-muted-foreground text-xs">
                {card.setId} · #{card.numberInSet} · {card.variant} · {card.language}
              </span>
            </span>
          </button>
        </li>
      ))}
    </ul>
  )
}

function SelectedCard({ card, onReset }: { card: Card; onReset: () => void }) {
  return (
    <div className="flex items-center gap-3 rounded-md border p-3">
      <span className="aspect-[5/7] w-12 shrink-0 overflow-hidden rounded bg-muted">
        {card.imageUrl ? (
          <img
            src={tcgdexImageUrl(card.imageUrl, 'low', 'webp')}
            alt=""
            className="h-full w-full object-contain"
          />
        ) : null}
      </span>
      <div className="flex flex-1 flex-col">
        <span className="font-medium text-sm">{card.name}</span>
        <span className="text-muted-foreground text-xs">
          {card.setId} · #{card.numberInSet} · {card.variant} · {card.language}
        </span>
      </div>
      <Button type="button" variant="ghost" size="sm" onClick={onReset}>
        Changer
      </Button>
    </div>
  )
}
