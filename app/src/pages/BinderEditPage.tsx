import { Link, useNavigate, useParams } from '@tanstack/react-router'
import { ArrowLeft, CheckCircle2, Loader2 } from 'lucide-react'
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
import { Skeleton } from '@/components/ui/skeleton'
import { useBinderQuery, useUpdateBinderMutation } from '@/hooks/useBindersHooks'

export function BinderEditPage() {
  const { binderId } = useParams({ from: '/binders/$binderId/edit' })
  const navigate = useNavigate({ from: '/binders/$binderId/edit' })
  const binderQuery = useBinderQuery(binderId)
  const update = useUpdateBinderMutation(binderId)

  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [pageCount, setPageCount] = useState(20)
  const [cols, setCols] = useState(3)
  const [rows, setRows] = useState(3)
  const [doubleSided, setDoubleSided] = useState(true)

  useEffect(() => {
    const binder = binderQuery.data
    if (!binder) return
    setName(binder.name)
    setDescription(binder.description ?? '')
    setPageCount(binder.pageCount)
    setCols(binder.cols)
    setRows(binder.rows)
    setDoubleSided(binder.doubleSided)
  }, [binderQuery.data])

  const previewCapacity =
    Math.max(0, pageCount) * Math.max(0, cols) * Math.max(0, rows) * (doubleSided ? 2 : 1)

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    update.mutate(
      {
        name: name.trim(),
        description: description.trim() === '' ? null : description.trim(),
        pageCount,
        cols,
        rows,
        doubleSided,
      },
      {
        onSuccess: () => {
          void navigate({ to: '/binders' })
        },
      },
    )
  }

  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-6 py-10">
      <Button variant="ghost" size="sm" asChild className="self-start">
        <Link to="/binders">
          <ArrowLeft />
          Retour aux classeurs
        </Link>
      </Button>

      <UICard>
        <CardHeader>
          <CardTitle>Modifier le classeur</CardTitle>
          <CardDescription>Mêmes champs qu'à la création.</CardDescription>
        </CardHeader>
        <CardContent>
          {binderQuery.isLoading ? (
            <Skeleton className="h-40 w-full" />
          ) : binderQuery.isError ? (
            <p className="text-destructive text-sm">
              Impossible de charger le classeur : {(binderQuery.error as Error).message}
            </p>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              <div className="flex flex-col gap-1">
                <label
                  htmlFor="binder-edit-name"
                  className="font-medium text-muted-foreground text-xs"
                >
                  Nom
                </label>
                <Input
                  id="binder-edit-name"
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  required
                />
              </div>
              <div className="flex flex-col gap-1">
                <label
                  htmlFor="binder-edit-description"
                  className="font-medium text-muted-foreground text-xs"
                >
                  Description (optionnelle)
                </label>
                <Input
                  id="binder-edit-description"
                  value={description}
                  onChange={(event) => setDescription(event.target.value)}
                />
              </div>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <NumericField
                  id="binder-edit-pageCount"
                  label="Pages"
                  value={pageCount}
                  onChange={setPageCount}
                />
                <NumericField
                  id="binder-edit-cols"
                  label="Colonnes"
                  value={cols}
                  onChange={setCols}
                />
                <NumericField
                  id="binder-edit-rows"
                  label="Lignes"
                  value={rows}
                  onChange={setRows}
                />
              </div>
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={doubleSided}
                  onChange={(event) => setDoubleSided(event.target.checked)}
                  className="size-4"
                />
                <span>Pages recto-verso (×2 sur la capacité)</span>
              </label>
              <p className="text-muted-foreground text-sm">
                Capacité dérivée :{' '}
                <span className="font-semibold text-foreground">{previewCapacity}</span> slots
              </p>
              <div className="flex items-center gap-3">
                <Button
                  type="submit"
                  disabled={update.isPending || name.trim() === '' || previewCapacity === 0}
                >
                  {update.isPending ? (
                    <>
                      <Loader2 className="animate-spin" />
                      Enregistrement…
                    </>
                  ) : (
                    <>
                      <CheckCircle2 />
                      Enregistrer
                    </>
                  )}
                </Button>
                {update.isError ? (
                  <span className="text-destructive text-sm">
                    {(update.error as Error).message}
                  </span>
                ) : null}
              </div>
            </form>
          )}
        </CardContent>
      </UICard>
    </main>
  )
}

function NumericField({
  id,
  label,
  value,
  onChange,
}: {
  id: string
  label: string
  value: number
  onChange: (value: number) => void
}) {
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="font-medium text-muted-foreground text-xs">
        {label}
      </label>
      <Input
        id={id}
        type="number"
        min={1}
        value={value}
        onChange={(event) => {
          const parsed = Number.parseInt(event.target.value, 10)
          if (!Number.isNaN(parsed)) {
            onChange(parsed)
          }
        }}
      />
    </div>
  )
}
