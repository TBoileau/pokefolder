import { Link, useParams } from '@tanstack/react-router'
import { ArrowLeft, Library, Trash2, X } from 'lucide-react'
import { useState } from 'react'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useCardQuery,
  useDeleteOwnedCardMutation,
  useOwnedCardsByCardQuery,
  useUpdateConditionMutation,
} from '@/hooks/useOwnedCardsHooks'
import { tcgdexImageUrl } from '@/lib/tcgdex'
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

export function CollectionCardPage() {
  const { cardId } = useParams({ from: '/collection/cards/$cardId' })
  const cardQuery = useCardQuery(cardId)
  const ownedQuery = useOwnedCardsByCardQuery(cardId)
  const updateCondition = useUpdateConditionMutation(cardId)
  const deleteOwnedCard = useDeleteOwnedCardMutation(cardId)

  const card = cardQuery.data
  const ownedCopies = ownedQuery.data?.member ?? []

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
          <Link to="/collection" search={{ page: 1 }}>
            <ArrowLeft />
            Retour à la collection
          </Link>
        </Button>

        {cardQuery.isLoading ? (
          <Skeleton className="h-40 w-full" />
        ) : card ? (
          <UICard>
            <div className="grid grid-cols-1 gap-6 p-6 md:grid-cols-[200px_1fr]">
              <div className="aspect-[5/7] w-full bg-muted">
                {card.imageUrl ? (
                  <img
                    src={tcgdexImageUrl(card.imageUrl, 'high', 'webp')}
                    alt={card.name}
                    className="h-full w-full object-contain"
                  />
                ) : null}
              </div>
              <div className="flex flex-col gap-2">
                <h2 className="font-semibold text-2xl tracking-tight">{card.name}</h2>
                <p className="text-muted-foreground text-sm">
                  {card.setId} · #{card.numberInSet} · {card.variant} · {card.language}
                </p>
                <p className="text-muted-foreground text-xs">{card.rarity}</p>
              </div>
            </div>
          </UICard>
        ) : (
          <p className="text-destructive">Carte introuvable.</p>
        )}

        <UICard>
          <CardHeader>
            <CardTitle>Exemplaires possédés</CardTitle>
            <CardDescription>
              Chaque ligne représente une carte physique. Modifier la condition met à jour
              l'exemplaire correspondant.
            </CardDescription>
          </CardHeader>
          <CardContent className="flex flex-col gap-3">
            {ownedQuery.isLoading ? (
              <Skeleton className="h-20 w-full" />
            ) : ownedCopies.length === 0 ? (
              <p className="text-muted-foreground text-sm">
                Aucun exemplaire de cette carte dans la collection.
              </p>
            ) : (
              ownedCopies.map((copy, index) => (
                <CopyRow
                  key={copy.id}
                  index={index + 1}
                  copy={copy}
                  isUpdating={
                    updateCondition.isPending && updateCondition.variables?.ownedCardId === copy.id
                  }
                  isDeleting={deleteOwnedCard.isPending && deleteOwnedCard.variables === copy.id}
                  onChangeCondition={(condition) =>
                    updateCondition.mutate({ ownedCardId: copy.id, condition })
                  }
                  onDelete={() => deleteOwnedCard.mutate(copy.id)}
                />
              ))
            )}
            {updateCondition.isError ? (
              <p className="text-destructive text-sm">
                Échec de la mise à jour : {(updateCondition.error as Error).message}
              </p>
            ) : null}
            {deleteOwnedCard.isError ? (
              <p className="text-destructive text-sm">
                Échec de la suppression : {(deleteOwnedCard.error as Error).message}
              </p>
            ) : null}
          </CardContent>
        </UICard>
      </main>
    </div>
  )
}

function CopyRow({
  index,
  copy,
  isUpdating,
  isDeleting,
  onChangeCondition,
  onDelete,
}: {
  index: number
  copy: OwnedCard
  isUpdating: boolean
  isDeleting: boolean
  onChangeCondition: (condition: Condition) => void
  onDelete: () => void
}) {
  const [confirming, setConfirming] = useState(false)

  return (
    <div className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-center gap-3">
        <span className="inline-flex size-7 items-center justify-center rounded-full bg-muted font-medium text-muted-foreground text-xs">
          #{index}
        </span>
        <div>
          <p className="font-medium text-sm">Exemplaire #{index}</p>
          <p className="text-muted-foreground text-xs">
            Ajouté le{' '}
            {new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(
              new Date(copy.createdAt),
            )}
          </p>
        </div>
      </div>
      <div className="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:gap-3">
        <div className="sm:w-56">
          <Select
            value={copy.condition}
            onValueChange={(value) => onChangeCondition(value as Condition)}
            disabled={isUpdating || isDeleting || confirming}
          >
            <SelectTrigger aria-label="Condition de l'exemplaire">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {CONDITIONS.map((condition) => (
                <SelectItem key={condition.value} value={condition.value}>
                  {condition.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        {confirming ? (
          <div className="flex items-center gap-2">
            <span className="text-muted-foreground text-xs">Supprimer définitivement ?</span>
            <Button
              variant="destructive"
              size="sm"
              onClick={onDelete}
              disabled={isDeleting}
              aria-label="Confirmer la suppression"
            >
              <Trash2 />
              {isDeleting ? 'Suppression…' : 'Confirmer'}
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setConfirming(false)}
              disabled={isDeleting}
              aria-label="Annuler la suppression"
            >
              <X />
              Annuler
            </Button>
          </div>
        ) : (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setConfirming(true)}
            disabled={isUpdating || isDeleting}
            aria-label="Supprimer cet exemplaire"
            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
          >
            <Trash2 />
            Supprimer
          </Button>
        )}
      </div>
    </div>
  )
}
