import { Link, useNavigate, useSearch } from '@tanstack/react-router'
import { ChevronLeft, ChevronRight, Library } from 'lucide-react'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { CARDS_PER_PAGE, useCardsQuery } from '@/hooks/useCardsQuery'
import type { Card } from '@/types/card'

export function CardsPage() {
  const { page } = useSearch({ from: '/cards' })
  const navigate = useNavigate({ from: '/cards' })
  const { data, isLoading, isError, error, isPlaceholderData } = useCardsQuery(page)

  const totalItems = data?.totalItems ?? 0
  const totalPages = Math.max(1, Math.ceil(totalItems / CARDS_PER_PAGE))
  const cards = data?.member ?? []

  const goToPage = (next: number) => {
    void navigate({ search: { page: next } })
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
        <div className="space-y-1">
          <h2 className="font-semibold text-2xl tracking-tight">Catalogue Pokémon TCG</h2>
          <p className="text-muted-foreground text-sm">
            {totalItems > 0
              ? `${totalItems} cartes synchronisées depuis TCGdex.`
              : 'Aucune carte synchronisée pour le moment.'}
          </p>
        </div>

        {isError ? (
          <ErrorState message={(error as Error).message} />
        ) : isLoading ? (
          <CardsGridSkeleton />
        ) : cards.length === 0 ? (
          <EmptyState />
        ) : (
          <div
            className={isPlaceholderData ? 'opacity-60 transition-opacity' : 'transition-opacity'}
          >
            <CardsGrid cards={cards} />
          </div>
        )}

        {totalPages > 1 && (
          <PaginationBar
            page={page}
            totalPages={totalPages}
            disabled={isLoading || isError}
            onPrev={() => goToPage(page - 1)}
            onNext={() => goToPage(page + 1)}
          />
        )}
      </main>
    </div>
  )
}

function CardsGrid({ cards }: { cards: Card[] }) {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      {cards.map((card) => (
        <li key={card['@id']}>
          <UICard className="h-full overflow-hidden">
            <div className="aspect-[5/7] w-full bg-muted">
              {card.imageUrl ? (
                <img
                  src={card.imageUrl}
                  alt={card.name}
                  loading="lazy"
                  className="h-full w-full object-contain"
                />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-muted-foreground text-xs">
                  no image
                </div>
              )}
            </div>
            <CardHeader className="gap-1 p-4">
              <CardTitle className="line-clamp-1">{card.name}</CardTitle>
              <CardDescription>
                {card.setId} · #{card.numberInSet}
              </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-wrap gap-1 p-4 pt-0">
              <Tag>{card.variant}</Tag>
              <Tag>{card.language}</Tag>
              <Tag>{card.rarity}</Tag>
            </CardContent>
          </UICard>
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

function CardsGridSkeleton() {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      {Array.from({ length: CARDS_PER_PAGE }, (_unused, i) => i).map((idx) => (
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

function EmptyState() {
  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
      <p className="font-medium">Le catalogue est vide.</p>
      <p className="max-w-md text-muted-foreground text-sm">
        Lance une synchronisation pour importer les cartes depuis TCGdex. En attendant la slice Sync
        UI : <code>bin/console pokefolder:sync-set base1</code> puis{' '}
        <code>bin/console messenger:consume async</code>.
      </p>
    </div>
  )
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="flex flex-col gap-2 rounded-xl border border-destructive/40 bg-destructive/5 p-6">
      <p className="font-medium text-destructive">Impossible de charger le catalogue.</p>
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
