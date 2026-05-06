import { Link, useNavigate } from '@tanstack/react-router'
import { ArrowLeft, CheckCircle2, Folder, Loader2, Search } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { useBindersQuery, useSuggestPlacementMutation } from '@/hooks/useBindersHooks'
import { useCardSearchQuery } from '@/hooks/useCardSearchQuery'
import { useCreateOwnedCardMutation } from '@/hooks/useCreateOwnedCardMutation'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { Binder } from '@/types/binder'
import type { Card } from '@/types/card'
import type { Condition, OwnedCard } from '@/types/ownedCard'

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

type SuggestionDialogState =
  | { phase: 'closed' }
  | { phase: 'suggested'; ownedCard: OwnedCard; suggestedBinderId: string | null }
  | { phase: 'choosing'; ownedCard: OwnedCard }

export function CollectionAddPage() {
  const navigate = useNavigate({ from: '/collection/add' })
  const [searchInput, setSearchInput] = useState('')
  const [debouncedQuery, setDebouncedQuery] = useState('')
  const [selectedCard, setSelectedCard] = useState<Card | null>(null)
  const [condition, setCondition] = useState<Condition>('NM')
  const [dialog, setDialog] = useState<SuggestionDialogState>({ phase: 'closed' })

  const createOwned = useCreateOwnedCardMutation()
  const suggestPlacement = useSuggestPlacementMutation()

  useEffect(() => {
    if (selectedCard !== null) return
    const id = window.setTimeout(() => setDebouncedQuery(searchInput), 250)
    return () => window.clearTimeout(id)
  }, [searchInput, selectedCard])

  const search = useCardSearchQuery(debouncedQuery)
  const showSuggestions = selectedCard === null && debouncedQuery.trim().length >= 2

  const goToCollectionCardView = (cardId: string) => {
    void navigate({ to: '/collection/cards/$cardId', params: { cardId } })
  }

  const goToBinder = (binderId: string) => {
    void navigate({
      to: '/binders/$binderId',
      params: { binderId },
    })
  }

  const closeAndStayFree = (ownedCard: OwnedCard) => {
    setDialog({ phase: 'closed' })
    toast.success('Carte ajoutée — laissée libre')
    goToCollectionCardView(ownedCard.card.id)
  }

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (selectedCard === null) return
    createOwned.mutate(
      { cardIri: selectedCard['@id'], condition },
      {
        onSuccess: (ownedCard) => {
          suggestPlacement.mutate(ownedCard.id, {
            onSuccess: (suggestion) => {
              setDialog({
                phase: 'suggested',
                ownedCard,
                suggestedBinderId: suggestion.binderId,
              })
            },
            onError: () => {
              toast.error('Suggestion indisponible — la carte reste libre.')
              goToCollectionCardView(ownedCard.card.id)
            },
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
    <>
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
                <Button
                  type="submit"
                  disabled={
                    selectedCard === null || createOwned.isPending || suggestPlacement.isPending
                  }
                >
                  {createOwned.isPending || suggestPlacement.isPending ? (
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

      <SuggestionDialog
        state={dialog}
        suggestedBinderId={dialog.phase === 'suggested' ? dialog.suggestedBinderId : null}
        onPlaceInSuggested={(ownedCard, binderId) => {
          setDialog({ phase: 'closed' })
          toast.message('Choisis un slot dans le classeur')
          goToBinder(binderId)
          void ownedCard
        }}
        onChooseAnother={(ownedCard) => setDialog({ phase: 'choosing', ownedCard })}
        onLeaveFree={closeAndStayFree}
        onPickBinder={(ownedCard, binderId) => {
          setDialog({ phase: 'closed' })
          toast.message('Choisis un slot dans le classeur')
          goToBinder(binderId)
          void ownedCard
        }}
        onCancelChoosing={(ownedCard) =>
          setDialog({
            phase: 'suggested',
            ownedCard,
            suggestedBinderId: suggestPlacement.data?.binderId ?? null,
          })
        }
        onClose={(ownedCard) => closeAndStayFree(ownedCard)}
      />
    </>
  )
}

function SuggestionDialog({
  state,
  suggestedBinderId,
  onPlaceInSuggested,
  onChooseAnother,
  onLeaveFree,
  onPickBinder,
  onCancelChoosing,
  onClose,
}: {
  state: SuggestionDialogState
  suggestedBinderId: string | null
  onPlaceInSuggested: (ownedCard: OwnedCard, binderId: string) => void
  onChooseAnother: (ownedCard: OwnedCard) => void
  onLeaveFree: (ownedCard: OwnedCard) => void
  onPickBinder: (ownedCard: OwnedCard, binderId: string) => void
  onCancelChoosing: (ownedCard: OwnedCard) => void
  onClose: (ownedCard: OwnedCard) => void
}) {
  const isOpen = state.phase !== 'closed'
  const ownedCard = state.phase === 'closed' ? null : state.ownedCard

  const bindersQuery = useBindersQuery()
  const binders = bindersQuery.data?.member ?? []
  const indexedBinders = useMemo(() => {
    const map = new Map<string, Binder>()
    for (const binder of binders) map.set(binder.id, binder)
    return map
  }, [binders])

  const suggestedBinder = suggestedBinderId ? indexedBinders.get(suggestedBinderId) : null

  return (
    <Dialog
      open={isOpen}
      onOpenChange={(open) => {
        if (!open && ownedCard) onClose(ownedCard)
      }}
    >
      <DialogContent>
        {state.phase === 'suggested' ? (
          <>
            <DialogHeader>
              <DialogTitle>
                {state.suggestedBinderId ? 'Classeur suggéré' : 'Aucun classeur ne correspond'}
              </DialogTitle>
              <DialogDescription>
                {state.suggestedBinderId
                  ? suggestedBinder
                    ? `${suggestedBinder.name} contient déjà des cartes du même set et a au moins un slot libre.`
                    : 'Un classeur correspond, mais ses détails ne sont pas chargés.'
                  : 'Aucun classeur ne contient déjà des cartes du même set avec un slot libre. La carte restera libre par défaut.'}
              </DialogDescription>
            </DialogHeader>

            {suggestedBinder ? (
              <div className="rounded-md border bg-muted/30 p-3 text-sm">
                <p className="font-medium">{suggestedBinder.name}</p>
                <p className="text-muted-foreground text-xs">
                  Capacité totale {suggestedBinder.capacity} slots — {suggestedBinder.pageCount}{' '}
                  pages × {suggestedBinder.cols}×{suggestedBinder.rows}
                  {suggestedBinder.doubleSided ? ' × recto-verso' : ''}
                </p>
              </div>
            ) : null}

            <DialogFooter>
              <Button variant="ghost" onClick={() => ownedCard && onLeaveFree(ownedCard)}>
                Laisser libre
              </Button>
              <Button
                variant="outline"
                onClick={() => ownedCard && onChooseAnother(ownedCard)}
                disabled={binders.length === 0}
              >
                Choisir un autre classeur
              </Button>
              {state.suggestedBinderId && suggestedBinder ? (
                <Button
                  onClick={() =>
                    ownedCard &&
                    state.suggestedBinderId &&
                    onPlaceInSuggested(ownedCard, state.suggestedBinderId)
                  }
                >
                  <Folder />
                  Placer dans ce classeur
                </Button>
              ) : null}
            </DialogFooter>
          </>
        ) : state.phase === 'choosing' ? (
          <>
            <DialogHeader>
              <DialogTitle>Choisir un classeur</DialogTitle>
              <DialogDescription>
                Sélectionne le classeur où tu veux placer la carte. Tu choisiras le slot après.
              </DialogDescription>
            </DialogHeader>

            {bindersQuery.isLoading ? (
              <div className="flex flex-col gap-2">
                <Skeleton className="h-12 w-full" />
                <Skeleton className="h-12 w-full" />
              </div>
            ) : binders.length === 0 ? (
              <p className="text-muted-foreground text-sm">Tu n'as pas encore créé de classeur.</p>
            ) : (
              <ul className="flex max-h-72 flex-col divide-y overflow-y-auto rounded-md border">
                {binders.map((binder) => (
                  <li key={binder.id}>
                    <button
                      type="button"
                      onClick={() => ownedCard && onPickBinder(ownedCard, binder.id)}
                      className="flex w-full flex-col items-start gap-0.5 px-3 py-2 text-left transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
                    >
                      <span className="font-medium text-sm">{binder.name}</span>
                      <span className="text-muted-foreground text-xs">
                        {binder.capacity} slots — {binder.pageCount} pages × {binder.cols}×
                        {binder.rows}
                        {binder.doubleSided ? ' × recto-verso' : ''}
                      </span>
                    </button>
                  </li>
                ))}
              </ul>
            )}

            <DialogFooter>
              <Button variant="ghost" onClick={() => ownedCard && onCancelChoosing(ownedCard)}>
                Retour
              </Button>
              <Button variant="outline" onClick={() => ownedCard && onLeaveFree(ownedCard)}>
                Laisser libre
              </Button>
            </DialogFooter>
          </>
        ) : null}
      </DialogContent>
    </Dialog>
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
