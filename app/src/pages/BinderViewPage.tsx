import { Link, useNavigate, useParams, useSearch } from '@tanstack/react-router'
import { ArrowLeft, ChevronLeft, ChevronRight, Library } from 'lucide-react'
import { useMemo } from 'react'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { useBinderQuery, useBinderSlotsQuery } from '@/hooks/useBindersHooks'
import { tcgdexImageUrl } from '@/lib/tcgdex'
import type { Binder } from '@/types/binder'
import type { BinderSlot, BinderSlotFace } from '@/types/binderSlot'

export function BinderViewPage() {
  const { binderId } = useParams({ from: '/binders/$binderId' })
  const navigate = useNavigate({ from: '/binders/$binderId' })
  const search = useSearch({ from: '/binders/$binderId' })

  const binderQuery = useBinderQuery(binderId)
  const slotsQuery = useBinderSlotsQuery(binderId)

  const binder = binderQuery.data
  const slots = slotsQuery.data?.member ?? []

  const slotIndex = useMemo(() => indexSlots(slots), [slots])

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

  return (
    <div className="flex min-h-svh flex-col">
      <header className="border-b">
        <div className="mx-auto flex max-w-5xl items-center gap-3 px-6 py-4">
          <Link to="/" className="flex items-center gap-3 text-foreground">
            <Library className="size-6 text-primary" />
            <h1 className="font-semibold text-lg tracking-tight">pokefolder</h1>
          </Link>
        </div>
      </header>

      <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-6 py-10">
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
          <>
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
                  <FaceToggle current={currentFace} onChange={(face) => updateSearch({ face })} />
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
          </>
        )}
      </main>
    </div>
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
        <SlotCell key={`${row}-${col}`} row={row} col={col} slot={slot} />
      ))}
    </div>
  )
}

function SlotCell({ row, col, slot }: { row: number; col: number; slot: BinderSlot | undefined }) {
  if (!slot?.ownedCard) {
    return (
      <div
        role="img"
        aria-label={`Slot vide ligne ${row} colonne ${col}`}
        className="flex aspect-[5/7] items-center justify-center rounded-md border-2 border-dashed bg-muted/30 text-muted-foreground/60 text-xs"
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

function ErrorState({ message }: { message: string }) {
  return (
    <div className="rounded-md border border-destructive/40 bg-destructive/5 p-4 text-destructive text-sm">
      {message}
    </div>
  )
}
