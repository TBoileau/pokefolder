import { Link } from '@tanstack/react-router'
import { CheckCircle2, RefreshCw } from 'lucide-react'
import { useState } from 'react'

import { Button } from '@/components/ui/button'
import {
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Card as UICard,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { useSyncAllMutation, useSyncSetMutation } from '@/hooks/useSyncMutations'

export function SyncPage() {
  const syncAll = useSyncAllMutation()
  const syncSet = useSyncSetMutation()
  const [setIdInput, setSetIdInput] = useState('')

  const handleSyncSet = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const value = setIdInput.trim()
    if (value === '') return
    syncSet.mutate(value)
  }

  return (
    <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-6 py-10">
      <div className="space-y-1">
        <h2 className="font-semibold text-2xl tracking-tight">Synchronisation TCGdex</h2>
        <p className="text-muted-foreground text-sm">
          Déclenche la synchro depuis l'UI plutôt que la CLI. Les messages sont posés sur la queue
          RabbitMQ ; un worker (<code>bin/console messenger:consume async</code>) les consomme.
        </p>
      </div>

      <UICard>
        <CardHeader>
          <CardTitle>Synchroniser tout le catalogue</CardTitle>
          <CardDescription>
            Liste tous les sets disponibles dans la première langue configurée et dispatche un
            message <code>SyncSet</code> par set.
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <Button onClick={() => syncAll.mutate()} disabled={syncAll.isPending} size="lg">
            <RefreshCw />
            {syncAll.isPending ? 'Dispatch en cours…' : 'Synchroniser tout'}
          </Button>
          <StatusLine
            isPending={syncAll.isPending}
            isError={syncAll.isError}
            isSuccess={syncAll.isSuccess}
            successMessage="SyncAll dispatché — un message par set sera bientôt en queue."
            errorMessage={syncAll.error?.message}
          />
        </CardContent>
      </UICard>

      <UICard>
        <CardHeader>
          <CardTitle>Synchroniser un set précis</CardTitle>
          <CardDescription>
            Utile quand un nouveau set sort entre deux syncs globales. Identifiant TCGdex (ex.{' '}
            <code>base1</code>, <code>swsh1</code>).
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSyncSet} className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="flex flex-1 flex-col gap-1">
              <label htmlFor="sync-set-id" className="font-medium text-muted-foreground text-xs">
                Set ID
              </label>
              <Input
                id="sync-set-id"
                value={setIdInput}
                onChange={(event) => setSetIdInput(event.target.value)}
                placeholder="base1"
                required
              />
            </div>
            <Button type="submit" disabled={syncSet.isPending || setIdInput.trim() === ''}>
              <RefreshCw />
              {syncSet.isPending ? 'Dispatch en cours…' : 'Synchroniser ce set'}
            </Button>
          </form>
          <div className="mt-3">
            <StatusLine
              isPending={syncSet.isPending}
              isError={syncSet.isError}
              isSuccess={syncSet.isSuccess}
              successMessage={
                syncSet.data ? `SyncSet "${syncSet.data.setId ?? ''}" dispatché.` : 'Dispatché.'
              }
              errorMessage={syncSet.error?.message}
            />
          </div>
        </CardContent>
      </UICard>

      <p className="text-muted-foreground text-xs">
        Une fois le worker démarré, le résultat se reflètera sur la page{' '}
        <Link to="/cards" search={{}} className="underline">
          /cards
        </Link>
        .
      </p>
    </main>
  )
}

function StatusLine({
  isPending,
  isError,
  isSuccess,
  successMessage,
  errorMessage,
}: {
  isPending: boolean
  isError: boolean
  isSuccess: boolean
  successMessage: string
  errorMessage: string | undefined
}) {
  if (isError) {
    return <p className="text-destructive text-sm">Échec : {errorMessage ?? 'erreur inconnue'}</p>
  }
  if (isSuccess) {
    return (
      <p className="inline-flex items-center gap-1 text-green-700 text-sm dark:text-green-400">
        <CheckCircle2 className="size-4" />
        {successMessage}
      </p>
    )
  }
  if (isPending) {
    return <p className="text-muted-foreground text-sm">Envoi du message…</p>
  }
  return null
}
