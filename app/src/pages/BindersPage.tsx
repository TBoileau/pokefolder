import { Link } from '@tanstack/react-router'
import { Library } from 'lucide-react'

import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { useBindersQuery } from '@/hooks/useBindersHooks'
import type { Binder } from '@/types/binder'

export function BindersPage() {
  const bindersQuery = useBindersQuery()
  const binders = bindersQuery.data?.member ?? []

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
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-1">
            <h2 className="font-semibold text-2xl tracking-tight">Mes classeurs</h2>
            <p className="text-muted-foreground text-sm">
              Mirroir physique de tes classeurs. La capacité est dérivée de pages × format ×
              recto-verso.
            </p>
          </div>
        </div>

        {bindersQuery.isLoading ? (
          <BindersGridSkeleton />
        ) : bindersQuery.isError ? (
          <ErrorState message={(bindersQuery.error as Error).message} />
        ) : binders.length === 0 ? (
          <EmptyState />
        ) : (
          <BindersGrid binders={binders} />
        )}
      </main>
    </div>
  )
}

function BindersGrid({ binders }: { binders: Binder[] }) {
  return (
    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {binders.map((binder) => (
        <li key={binder.id}>
          <UICard className="h-full">
            <CardHeader>
              <CardTitle>{binder.name}</CardTitle>
              {binder.description ? (
                <CardDescription className="line-clamp-2">{binder.description}</CardDescription>
              ) : null}
            </CardHeader>
            <CardContent className="flex flex-col gap-2 text-muted-foreground text-sm">
              <p>
                Capacité : <span className="font-semibold text-foreground">{binder.capacity}</span>{' '}
                slots
              </p>
              <p className="text-xs">
                {binder.pageCount} pages × {binder.cols}×{binder.rows}
                {binder.doubleSided ? ' × recto-verso' : ''}
              </p>
            </CardContent>
          </UICard>
        </li>
      ))}
    </ul>
  )
}

function BindersGridSkeleton() {
  return (
    <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {Array.from({ length: 3 }, (_unused, i) => i).map((idx) => (
        <li key={idx}>
          <UICard>
            <CardHeader>
              <Skeleton className="h-5 w-3/4" />
              <Skeleton className="h-4 w-full" />
            </CardHeader>
            <CardContent className="space-y-2">
              <Skeleton className="h-4 w-1/2" />
              <Skeleton className="h-3 w-1/3" />
            </CardContent>
          </UICard>
        </li>
      ))}
    </ul>
  )
}

function EmptyState() {
  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center">
      <p className="font-medium">Aucun classeur pour l'instant.</p>
      <p className="text-muted-foreground text-sm">
        Crée ton premier classeur depuis la slice "création" (#19).
      </p>
    </div>
  )
}

function ErrorState({ message }: { message: string }) {
  return (
    <div className="flex flex-col gap-2 rounded-xl border border-destructive/40 bg-destructive/5 p-6">
      <p className="font-medium text-destructive">Impossible de charger les classeurs.</p>
      <p className="text-sm">{message}</p>
    </div>
  )
}
