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
import { Link, useParams } from '@tanstack/react-router'
import { ArrowLeft, Trash2 } from 'lucide-react'
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
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuTrigger,
} from '@/components/ui/context-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Skeleton } from '@/components/ui/skeleton'
import {
  PlacementHttpError,
  useBinderQuery,
  useBinderSlotsQuery,
  useMoveCardMutation,
  usePlaceCardMutation,
  useUnplaceCardMutation,
} from '@/hooks/useBindersHooks'
import { useFreeOwnedCardsQuery } from '@/hooks/useOwnedCardsHooks'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { Binder } from '@/types/binder'
import type { BinderSlot, BinderSlotFace } from '@/types/binderSlot'
import type { OwnedCard } from '@/types/ownedCard'

const DROPPABLE_PREFIX = 'slot:'
const DRAGGABLE_FREE = 'free:'
const DRAGGABLE_SLOT = 'placed:'

export function BinderViewPage() {
  const { binderId } = useParams({ from: '/binders/$binderId' })

  const binderQuery = useBinderQuery(binderId)
  const slotsQuery = useBinderSlotsQuery(binderId)
  const freeCardsQuery = useFreeOwnedCardsQuery()
  const placeCard = usePlaceCardMutation(binderId)
  const moveCard = useMoveCardMutation(binderId)
  const unplaceCard = useUnplaceCardMutation(binderId)

  const [unplaceTarget, setUnplaceTarget] = useState<NonNullable<BinderSlot['ownedCard']> | null>(
    null,
  )

  const confirmUnplace = () => {
    if (!unplaceTarget) return
    const target = unplaceTarget
    unplaceCard.mutate(target.id, {
      onSuccess: () => {
        toast.success('Carte retirée du classeur')
        setUnplaceTarget(null)
      },
      onError: (error) => {
        toast.error(`Échec : ${(error as Error).message}`)
        setUnplaceTarget(null)
      },
    })
  }

  const binder = binderQuery.data
  const slots = slotsQuery.data?.member ?? []
  const freeCards = freeCardsQuery.data?.member ?? []

  const slotIndex = useMemo(() => indexSlots(slots), [slots])
  const freeCardIndex = useMemo(() => {
    const map = new Map<string, OwnedCard>()
    for (const card of freeCards) map.set(card.id, card)
    return map
  }, [freeCards])
  const placedCardIndex = useMemo(() => {
    const map = new Map<string, NonNullable<BinderSlot['ownedCard']>>()
    for (const slot of slots) {
      if (slot.ownedCard) {
        map.set(slot.ownedCard.id, slot.ownedCard)
      }
    }
    return map
  }, [slots])

  const [activeOwnedCardId, setActiveOwnedCardId] = useState<string | null>(null)
  const [activeSource, setActiveSource] = useState<'free' | 'slot' | null>(null)
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }))

  const handleDragStart = (event: DragStartEvent) => {
    const id = String(event.active.id)
    if (id.startsWith(DRAGGABLE_FREE)) {
      setActiveOwnedCardId(id.slice(DRAGGABLE_FREE.length))
      setActiveSource('free')
    } else if (id.startsWith(DRAGGABLE_SLOT)) {
      setActiveOwnedCardId(id.slice(DRAGGABLE_SLOT.length))
      setActiveSource('slot')
    }
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const source = activeSource
    setActiveOwnedCardId(null)
    setActiveSource(null)

    const activeId = String(event.active.id)
    const overId = event.over ? String(event.over.id) : null

    let ownedCardId: string | null = null
    if (activeId.startsWith(DRAGGABLE_FREE)) ownedCardId = activeId.slice(DRAGGABLE_FREE.length)
    else if (activeId.startsWith(DRAGGABLE_SLOT))
      ownedCardId = activeId.slice(DRAGGABLE_SLOT.length)
    if (!ownedCardId) return

    if (!overId?.startsWith(DROPPABLE_PREFIX)) return
    const target = parseSlotId(overId.slice(DROPPABLE_PREFIX.length))
    if (!target) return

    const occupant = slotIndex.get(positionKey(target.page, target.face, target.row, target.col))
    if (occupant?.ownedCard) {
      if (occupant.ownedCard.id === ownedCardId) return
      toast.error('Slot déjà occupé')
      return
    }

    const refetchOnConflict = () => {
      void slotsQuery.refetch()
      void freeCardsQuery.refetch()
    }
    const handleError = (error: Error) => {
      if (error instanceof PlacementHttpError && error.status === 409) {
        toast.error('Conflit : slot devenu occupé. Vue rafraîchie.')
        refetchOnConflict()
      } else {
        toast.error(`Échec : ${error.message}`)
      }
    }

    if (source === 'free') {
      placeCard.mutate(
        {
          ownedCardId,
          pageNumber: target.page,
          face: target.face,
          row: target.row,
          col: target.col,
        },
        {
          onSuccess: () => toast.success('Carte placée'),
          onError: handleError,
        },
      )
    } else if (source === 'slot') {
      moveCard.mutate(
        {
          ownedCardId,
          binderId,
          pageNumber: target.page,
          face: target.face,
          row: target.row,
          col: target.col,
        },
        {
          onSuccess: () => toast.success('Carte déplacée'),
          onError: handleError,
        },
      )
    }
  }

  return (
    <DndContext sensors={sensors} onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
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
            <div className="flex flex-col gap-4">
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

              {slotsQuery.isLoading ? (
                <Skeleton className="h-96 w-full" />
              ) : slotsQuery.isError ? (
                <ErrorState message={(slotsQuery.error as Error).message} />
              ) : (
                <AllPages
                  binder={binder}
                  slotIndex={slotIndex}
                  onRequestUnplace={setUnplaceTarget}
                />
              )}
            </div>

            <FreeCardsPanel
              cards={freeCards}
              isLoading={freeCardsQuery.isLoading}
              isError={freeCardsQuery.isError}
            />
          </div>
        )}
      </main>

      <Dialog
        open={unplaceTarget !== null}
        onOpenChange={(open) => {
          if (!open && !unplaceCard.isPending) setUnplaceTarget(null)
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Retirer cette carte du classeur ?</DialogTitle>
            <DialogDescription>
              {unplaceTarget
                ? `${unplaceTarget.card.name} (${unplaceTarget.card.setId} #${unplaceTarget.card.numberInSet}) restera dans ta collection — seul son emplacement dans ce classeur sera libéré.`
                : null}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              variant="ghost"
              onClick={() => setUnplaceTarget(null)}
              disabled={unplaceCard.isPending}
            >
              Annuler
            </Button>
            <Button variant="destructive" onClick={confirmUnplace} disabled={unplaceCard.isPending}>
              <Trash2 />
              Retirer
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <DragOverlay dropAnimation={null}>
        {activeOwnedCardId ? (
          <DragPreview
            ownedCard={freeCardIndex.get(activeOwnedCardId)}
            placed={placedCardIndex.get(activeOwnedCardId)}
          />
        ) : null}
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

function AllPages({
  binder,
  slotIndex,
  onRequestUnplace,
}: {
  binder: Binder
  slotIndex: SlotIndex
  onRequestUnplace: (ownedCard: NonNullable<BinderSlot['ownedCard']>) => void
}) {
  const pages = Array.from({ length: binder.pageCount }, (_unused, i) => i + 1)

  return (
    <div className="flex flex-col gap-4">
      {pages.map((pageNumber) => (
        <PageBlock
          key={pageNumber}
          binder={binder}
          pageNumber={pageNumber}
          slotIndex={slotIndex}
          onRequestUnplace={onRequestUnplace}
        />
      ))}
    </div>
  )
}

function PageBlock({
  binder,
  pageNumber,
  slotIndex,
  onRequestUnplace,
}: {
  binder: Binder
  pageNumber: number
  slotIndex: SlotIndex
  onRequestUnplace: (ownedCard: NonNullable<BinderSlot['ownedCard']>) => void
}) {
  const isEven = pageNumber % 2 === 0

  return (
    <UICard
      className={`overflow-hidden ${
        isEven ? 'border-l-4 border-l-primary/40' : 'border-l-4 border-l-muted'
      }`}
    >
      <CardHeader className="bg-muted/30 py-3">
        <CardTitle className="text-base">Page {pageNumber}</CardTitle>
      </CardHeader>
      <CardContent className={`grid gap-4 pt-4 ${binder.doubleSided ? 'md:grid-cols-2' : ''}`}>
        <FaceGrid
          binder={binder}
          pageNumber={pageNumber}
          face="recto"
          slotIndex={slotIndex}
          onRequestUnplace={onRequestUnplace}
        />
        {binder.doubleSided ? (
          <FaceGrid
            binder={binder}
            pageNumber={pageNumber}
            face="verso"
            slotIndex={slotIndex}
            onRequestUnplace={onRequestUnplace}
          />
        ) : null}
      </CardContent>
    </UICard>
  )
}

function FaceGrid({
  binder,
  pageNumber,
  face,
  slotIndex,
  onRequestUnplace,
}: {
  binder: Binder
  pageNumber: number
  face: BinderSlotFace
  slotIndex: SlotIndex
  onRequestUnplace: (ownedCard: NonNullable<BinderSlot['ownedCard']>) => void
}) {
  const cells: { row: number; col: number; slot: BinderSlot | undefined }[] = []
  for (let row = 1; row <= binder.rows; row += 1) {
    for (let col = 1; col <= binder.cols; col += 1) {
      cells.push({ row, col, slot: slotIndex.get(positionKey(pageNumber, face, row, col)) })
    }
  }

  return (
    <div className="flex flex-col gap-2">
      <p className="font-medium text-muted-foreground text-xs uppercase tracking-wide">
        {face === 'recto' ? 'Recto' : 'Verso'}
      </p>
      <div
        className="grid gap-2"
        style={{ gridTemplateColumns: `repeat(${binder.cols}, minmax(0, 1fr))` }}
      >
        {cells.map(({ row, col, slot }) => (
          <SlotCell
            key={`${row}-${col}`}
            page={pageNumber}
            face={face}
            row={row}
            col={col}
            slot={slot}
            onRequestUnplace={onRequestUnplace}
          />
        ))}
      </div>
    </div>
  )
}

function SlotCell({
  page,
  face,
  row,
  col,
  slot,
  onRequestUnplace,
}: {
  page: number
  face: BinderSlotFace
  row: number
  col: number
  slot: BinderSlot | undefined
  onRequestUnplace: (ownedCard: NonNullable<BinderSlot['ownedCard']>) => void
}) {
  const droppableId = `${DROPPABLE_PREFIX}${positionKey(page, face, row, col)}`
  const { setNodeRef: setDroppableRef, isOver } = useDroppable({
    id: droppableId,
    disabled: !!slot?.ownedCard,
  })

  if (!slot?.ownedCard) {
    return (
      <div
        ref={setDroppableRef}
        role="img"
        aria-label={`Slot vide page ${page} ${face} ligne ${row} colonne ${col}`}
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

  return <OccupiedSlotCell ownedCard={slot.ownedCard} onRequestUnplace={onRequestUnplace} />
}

function OccupiedSlotCell({
  ownedCard,
  onRequestUnplace,
}: {
  ownedCard: NonNullable<BinderSlot['ownedCard']>
  onRequestUnplace: (ownedCard: NonNullable<BinderSlot['ownedCard']>) => void
}) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `${DRAGGABLE_SLOT}${ownedCard.id}`,
  })

  const { card } = ownedCard
  const tooltip = `${card.name} — ${card.setId} #${card.numberInSet} (${ownedCard.condition})`

  return (
    <ContextMenu>
      <ContextMenuTrigger asChild>
        <Link
          ref={setNodeRef}
          to="/collection/cards/$cardId"
          params={{ cardId: card.id }}
          title={tooltip}
          aria-label={tooltip}
          {...listeners}
          {...attributes}
          className={`group relative block aspect-[5/7] cursor-grab overflow-hidden rounded-md border bg-muted shadow-sm transition focus-visible:outline-2 focus-visible:outline-primary active:cursor-grabbing ${
            isDragging ? 'opacity-30' : 'hover:shadow-md'
          }`}
        >
          {card.imageUrl ? (
            <img
              src={tcgdexImageUrl(card.imageUrl, 'low')}
              alt={card.name}
              className="size-full object-cover"
              loading="lazy"
              draggable={false}
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
      </ContextMenuTrigger>
      <ContextMenuContent>
        <ContextMenuItem
          variant="destructive"
          onSelect={(event) => {
            event.preventDefault()
            onRequestUnplace(ownedCard)
          }}
        >
          <Trash2 />
          Retirer du classeur
        </ContextMenuItem>
      </ContextMenuContent>
    </ContextMenu>
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
    id: `${DRAGGABLE_FREE}${ownedCard.id}`,
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

function DragPreview({
  ownedCard,
  placed,
}: {
  ownedCard: OwnedCard | undefined
  placed: NonNullable<BinderSlot['ownedCard']> | undefined
}) {
  const card = ownedCard?.card ?? placed?.card
  if (!card) return null
  return (
    <div className="aspect-[5/7] w-24 overflow-hidden rounded-md border bg-muted shadow-lg ring-2 ring-primary">
      {card.imageUrl ? (
        <img
          src={tcgdexImageUrl(card.imageUrl, 'low')}
          alt={card.name}
          className="size-full object-cover"
        />
      ) : (
        <div className="flex size-full items-center justify-center text-center text-muted-foreground text-xs">
          {card.name}
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
