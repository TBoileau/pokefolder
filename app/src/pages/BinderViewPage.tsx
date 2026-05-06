import {
  DndContext,
  type DragEndEvent,
  DragOverlay,
  type DragStartEvent,
  PointerSensor,
  useDraggable,
  useDroppable,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import { Link, useNavigate, useParams, useSearch } from '@tanstack/react-router'
import { ArrowLeft, ChevronLeft, ChevronRight, Library } from 'lucide-react'
import { useMemo, useState } from 'react'
import { toast } from 'sonner'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
  PlacementHttpError,
  useBinderQuery,
  useBinderSlotsQuery,
  usePlaceCardMutation,
} from '@/hooks/useBindersHooks'
import { useFreeOwnedCardsQuery } from '@/hooks/useOwnedCardsHooks'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { Binder } from '@/types/binder'
import type { BinderSlot, BinderSlotFace } from '@/types/binderSlot'
import type { OwnedCard } from '@/types/ownedCard'

const DROPPABLE_PREFIX = 'slot:'
const DRAGGABLE_PREFIX = 'free:'

export function BinderViewPage() {
  const { binderId } = useParams({ from: '/binders/$binderId' })
  const navigate = useNavigate({ from: '/binders/$binderId' })
  const search = useSearch({ from: '/binders/$binderId' })

  const binderQuery = useBinderQuery(binderId)
  const slotsQuery = useBinderSlotsQuery(binderId)
  const freeCardsQuery = useFreeOwnedCardsQuery()
  const placeCard = usePlaceCardMutation(binderId)

  const binder = binderQuery.data
  const slots = slotsQuery.data?.member ?? []
  const freeCards = freeCardsQuery.data?.member ?? []

  const slotIndex = useMemo(() => indexSlots(slots), [slots])
  const freeCardIndex = useMemo(() => {
    const map = new Map<string, OwnedCard>()
    for (const card of freeCards) map.set(card.id, card)
    return map
  }, [freeCards])

  const [activeOwnedCardId, setActiveOwnedCardId] = useState<string | null>(null)
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }))

  const pageCount = binder?.pageCount ?? 1
  const currentPage = clamp(search.page, 1, pageCount)
  const currentFace: BinderSlotFace = binder && !binder.doubleSided ? 'recto' : search.face

  const updateSearch = (next: { page?: number; face?: BinderSlotFace }) => {
    void navigate({
      search: (prev) => ({
        page: next.page ?? prev.page,
        face: next.face ?? prev.face,
      }),
    })
  }

  const handleDragStart = (event: DragStartEvent) => {
    const id = String(event.active.id)
    if (id.startsWith(DRAGGABLE_PREFIX)) {
      setActiveOwnedCardId(id.slice(DRAGGABLE_PREFIX.length))
    }
  }

  const handleDragEnd = (event: DragEndEvent) => {
    setActiveOwnedCardId(null)
    const activeId = String(event.active.id)
    if (!activeId.startsWith(DRAGGABLE_PREFIX)) return
    const overId = event.over ? String(event.over.id) : null
    if (!overId?.startsWith(DROPPABLE_PREFIX)) return

    const ownedCardId = activeId.slice(DRAGGABLE_PREFIX.length)
    const target = parseSlotId(overId.slice(DROPPABLE_PREFIX.length))
    if (!target) return

    const occupant = slotIndex.get(positionKey(target.page, target.face, target.row, target.col))
    if (occupant?.ownedCard) {
      toast.error('Slot déjà occupé')
      return
    }

    placeCard.mutate(
      {
        ownedCardId,
        pageNumber: target.page,
        face: target.face,
        row: target.row,
        col: target.col,
      },
      {
        onSuccess: () => {
          toast.success('Carte placée')
        },
        onError: (error) => {
          if (error instanceof PlacementHttpError && error.status === 409) {
            toast.error('Conflit : slot devenu occupé. Vue rafraîchie.')
            void slotsQuery.refetch()
            void freeCardsQuery.refetch()
          } else {
            toast.error(`Échec : ${error.message}`)
          }
        },
      },
    )
  }

  return (
    <DndContext sensors={sensors} onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
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
          <Button variant="ghost" size="sm" asChild className="self-start">
            <Link to="/binders">
              <ArrowLeft />
              Retour aux classeurs
            </Link>
          </Button>

          {binderQuery.isLoading ? (
            <Skeleton className="h-32 w-full" />
          ) : binderQuery.isError || !binder ? (
            <ErrorState
              message={(binderQuery.error as Error | null)?.message ?? 'Classeur introuvable.'}
            />
          ) : (
            <div className="grid gap-6 lg:grid-cols-[1fr_280px]">
              <div className="flex flex-col gap-6">
                <div className="space-y-1">
                  <h2 className="font-semibold text-2xl tracking-tight">{binder.name}</h2>
                  {binder.description ? (
                    <p className="text-muted-foreground text-sm">{binder.description}</p>
                  ) : null}
                  <p className="text-muted-foreground text-xs">
                    {binder.pageCount} pages × {binder.cols}×{binder.rows}
                    {binder.doubleSided ? ' × recto-verso' : ''} —{' '}
                    <span className="font-semibold text-foreground">{binder.capacity}</span> slots
                  </p>
                </div>

                <UICard>
                  <CardHeader className="flex flex-row items-center justify-between gap-3">
                    <div>
                      <CardTitle>
                        Page {currentPage} / {pageCount}
                      </CardTitle>
                      <CardDescription>
                        {binder.doubleSided
                          ? `Face ${currentFace === 'recto' ? 'recto' : 'verso'}`
                          : 'Recto uniquement'}
                      </CardDescription>
                    </div>
                    <PageControls
                      pageCount={pageCount}
                      currentPage={currentPage}
                      onPrev={() => updateSearch({ page: Math.max(1, currentPage - 1) })}
                      onNext={() => updateSearch({ page: Math.min(pageCount, currentPage + 1) })}
                    />
                  </CardHeader>
                  <CardContent className="flex flex-col gap-4">
                    {binder.doubleSided ? (
                      <FaceToggle
                        current={currentFace}
                        onChange={(face) => updateSearch({ face })}
                      />
                    ) : null}
                    {slotsQuery.isLoading ? (
                      <Skeleton className="aspect-square w-full" />
                    ) : slotsQuery.isError ? (
                      <ErrorState message={(slotsQuery.error as Error).message} />
                    ) : (
                      <Grid
                        binder={binder}
                        page={currentPage}
                        face={currentFace}
                        slotIndex={slotIndex}
                      />
                    )}
                  </CardContent>
                </UICard>
              </div>

              <FreeCardsPanel
                cards={freeCards}
                isLoading={freeCardsQuery.isLoading}
                isError={freeCardsQuery.isError}
              />
            </div>
          )}
        </main>
      </div>

      <DragOverlay dropAnimation={null}>
        {activeOwnedCardId ? <DragPreview card={freeCardIndex.get(activeOwnedCardId)} /> : null}
      </DragOverlay>
    </DndContext>
  )
}

type SlotIndex = Map<string, BinderSlot>

function indexSlots(slots: BinderSlot[]): SlotIndex {
  const index: SlotIndex = new Map()
  for (const slot of slots) {
    index.set(positionKey(slot.pageNumber, slot.face, slot.row, slot.col), slot)
  }
  return index
}

function positionKey(page: number, face: BinderSlotFace, row: number, col: number): string {
  return `${page}|${face}|${row}|${col}`
}

function parseSlotId(
  raw: string,
): { page: number; face: BinderSlotFace; row: number; col: number } | null {
  const [pageStr, face, rowStr, colStr] = raw.split('|')
  if (!pageStr || !face || !rowStr || !colStr) return null
  if (face !== 'recto' && face !== 'verso') return null
  const page = Number.parseInt(pageStr, 10)
  const row = Number.parseInt(rowStr, 10)
  const col = Number.parseInt(colStr, 10)
  if ([page, row, col].some(Number.isNaN)) return null
  return { page, face, row, col }
}

function clamp(value: number, min: number, max: number): number {
  if (value < min) return min
  if (value > max) return max
  return value
}

function PageControls({
  pageCount,
  currentPage,
  onPrev,
  onNext,
}: {
  pageCount: number
  currentPage: number
  onPrev: () => void
  onNext: () => void
}) {
  return (
    <div className="flex items-center gap-1">
      <Button
        variant="ghost"
        size="icon"
        onClick={onPrev}
        disabled={currentPage <= 1}
        aria-label="Page précédente"
      >
        <ChevronLeft />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        onClick={onNext}
        disabled={currentPage >= pageCount}
        aria-label="Page suivante"
      >
        <ChevronRight />
      </Button>
    </div>
  )
}

function FaceToggle({
  current,
  onChange,
}: {
  current: BinderSlotFace
  onChange: (face: BinderSlotFace) => void
}) {
  return (
    <div className="inline-flex self-start rounded-md border bg-muted p-0.5 text-sm">
      <button
        type="button"
        onClick={() => onChange('recto')}
        className={`rounded px-3 py-1 transition ${
          current === 'recto' ? 'bg-background shadow-sm' : 'text-muted-foreground'
        }`}
      >
        Recto
      </button>
      <button
        type="button"
        onClick={() => onChange('verso')}
        className={`rounded px-3 py-1 transition ${
          current === 'verso' ? 'bg-background shadow-sm' : 'text-muted-foreground'
        }`}
      >
        Verso
      </button>
    </div>
  )
}

function Grid({
  binder,
  page,
  face,
  slotIndex,
}: {
  binder: Binder
  page: number
  face: BinderSlotFace
  slotIndex: SlotIndex
}) {
  const cells: { row: number; col: number; slot: BinderSlot | undefined }[] = []
  for (let row = 1; row <= binder.rows; row += 1) {
    for (let col = 1; col <= binder.cols; col += 1) {
      cells.push({ row, col, slot: slotIndex.get(positionKey(page, face, row, col)) })
    }
  }

  return (
    <div
      className="grid gap-3"
      style={{ gridTemplateColumns: `repeat(${binder.cols}, minmax(0, 1fr))` }}
    >
      {cells.map(({ row, col, slot }) => (
        <SlotCell key={`${row}-${col}`} page={page} face={face} row={row} col={col} slot={slot} />
      ))}
    </div>
  )
}

function SlotCell({
  page,
  face,
  row,
  col,
  slot,
}: {
  page: number
  face: BinderSlotFace
  row: number
  col: number
  slot: BinderSlot | undefined
}) {
  const droppableId = `${DROPPABLE_PREFIX}${positionKey(page, face, row, col)}`
  const isOccupied = !!slot?.ownedCard

  const { setNodeRef, isOver } = useDroppable({ id: droppableId, disabled: isOccupied })

  if (!slot?.ownedCard) {
    return (
      <div
        ref={setNodeRef}
        role="img"
        aria-label={`Slot vide ligne ${row} colonne ${col}`}
        className={`flex aspect-[5/7] items-center justify-center rounded-md border-2 border-dashed text-xs transition ${
          isOver
            ? 'border-primary bg-primary/10 text-primary'
            : 'border-border bg-muted/30 text-muted-foreground/60'
        }`}
      >
        {row}·{col}
      </div>
    )
  }

  const { card } = slot.ownedCard
  const tooltip = `${card.name} — ${card.setId} #${card.numberInSet} (${slot.ownedCard.condition})`

  return (
    <Link
      to="/collection/cards/$cardId"
      params={{ cardId: card.id }}
      title={tooltip}
      aria-label={tooltip}
      className="group relative block aspect-[5/7] overflow-hidden rounded-md border bg-muted shadow-sm transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-primary"
    >
      {card.imageUrl ? (
        <img
          src={tcgdexImageUrl(card.imageUrl, 'low')}
          alt={card.name}
          className="size-full object-cover"
          loading="lazy"
        />
      ) : (
        <div className="flex size-full items-center justify-center text-center text-muted-foreground text-xs">
          {card.name}
        </div>
      )}
      <span className="pointer-events-none absolute inset-x-0 bottom-0 truncate bg-background/80 px-1.5 py-0.5 text-[10px] opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
        {card.name}
      </span>
    </Link>
  )
}

function FreeCardsPanel({
  cards,
  isLoading,
  isError,
}: {
  cards: OwnedCard[]
  isLoading: boolean
  isError: boolean
}) {
  return (
    <UICard className="h-fit lg:sticky lg:top-6">
      <CardHeader>
        <CardTitle className="text-base">Cartes libres</CardTitle>
        <CardDescription>
          Glisse une carte vers un slot vide pour la placer. {cards.length} carte
          {cards.length > 1 ? 's' : ''} libre{cards.length > 1 ? 's' : ''}.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="grid grid-cols-3 gap-2">
            <Skeleton className="aspect-[5/7]" />
            <Skeleton className="aspect-[5/7]" />
            <Skeleton className="aspect-[5/7]" />
          </div>
        ) : isError ? (
          <p className="text-destructive text-sm">Impossible de charger les cartes libres.</p>
        ) : cards.length === 0 ? (
          <p className="text-muted-foreground text-sm">
            Toutes les cartes de ta collection sont déjà placées.
          </p>
        ) : (
          <ul className="grid max-h-[60vh] grid-cols-3 gap-2 overflow-y-auto pr-1">
            {cards.map((ownedCard) => (
              <li key={ownedCard.id}>
                <FreeCardItem ownedCard={ownedCard} />
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </UICard>
  )
}

function FreeCardItem({ ownedCard }: { ownedCard: OwnedCard }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `${DRAGGABLE_PREFIX}${ownedCard.id}`,
  })

  const tooltip = `${ownedCard.card.name} — ${ownedCard.card.setId} #${ownedCard.card.numberInSet} (${ownedCard.condition})`

  return (
    <button
      ref={setNodeRef}
      type="button"
      {...listeners}
      {...attributes}
      aria-label={`Glisser ${ownedCard.card.name}`}
      title={tooltip}
      className={`relative block aspect-[5/7] w-full cursor-grab overflow-hidden rounded-md border bg-muted text-xs shadow-sm transition active:cursor-grabbing ${
        isDragging ? 'opacity-30' : 'hover:shadow-md'
      }`}
    >
      {ownedCard.card.imageUrl ? (
        <img
          src={tcgdexImageUrl(ownedCard.card.imageUrl, 'low')}
          alt={ownedCard.card.name}
          className="size-full object-cover"
          loading="lazy"
          draggable={false}
        />
      ) : (
        <span className="line-clamp-2 px-1 py-2 text-muted-foreground">{ownedCard.card.name}</span>
      )}
    </button>
  )
}

function DragPreview({ card }: { card: OwnedCard | undefined }) {
  if (!card) return null
  return (
    <div className="aspect-[5/7] w-24 overflow-hidden rounded-md border bg-muted shadow-lg ring-2 ring-primary">
      {card.card.imageUrl ? (
        <img
          src={tcgdexImageUrl(card.card.imageUrl, 'low')}
          alt={card.card.name}
          className="size-full object-cover"
        />
      ) : (
        <div className="flex size-full items-center justify-center text-center text-muted-foreground text-xs">
          {card.card.name}
        </div>
      )}
    </div>
  )
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="rounded-md border border-destructive/40 bg-destructive/5 p-4 text-destructive text-sm">
      {message}
    </div>
  )
}
