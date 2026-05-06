import { Link, useNavigate } from '@tanstack/react-router'
import { ArrowLeft, CheckCircle2, Loader2 } from 'lucide-react'
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
import { useCreateBinderMutation } from '@/hooks/useBindersHooks'

export function BinderCreatePage() {
  const navigate = useNavigate({ from: '/binders/new' })
  const create = useCreateBinderMutation()

  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [pageCount, setPageCount] = useState(20)
  const [cols, setCols] = useState(3)
  const [rows, setRows] = useState(3)
  const [doubleSided, setDoubleSided] = useState(true)

  const previewCapacity =
    Math.max(0, pageCount) * Math.max(0, cols) * Math.max(0, rows) * (doubleSided ? 2 : 1)

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    create.mutate(
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
          <CardTitle>Nouveau classeur</CardTitle>
          <CardDescription>
            La capacité totale est dérivée à la volée — pages × format × recto-verso.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
              <label htmlFor="binder-name" className="font-medium text-muted-foreground text-xs">
                Nom
              </label>
              <Input
                id="binder-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="ex. Master Set Base"
                required
              />
            </div>

            <div className="flex flex-col gap-1">
              <label
                htmlFor="binder-description"
                className="font-medium text-muted-foreground text-xs"
              >
                Description (optionnelle)
              </label>
              <Input
                id="binder-description"
                value={description}
                onChange={(event) => setDescription(event.target.value)}
                placeholder="Pour quoi sert ce classeur ?"
              />
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <NumericField
                id="binder-pageCount"
                label="Pages"
                value={pageCount}
                onChange={setPageCount}
                min={1}
              />
              <NumericField
                id="binder-cols"
                label="Colonnes / page"
                value={cols}
                onChange={setCols}
                min={1}
              />
              <NumericField
                id="binder-rows"
                label="Lignes / page"
                value={rows}
                onChange={setRows}
                min={1}
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
                disabled={create.isPending || name.trim() === '' || previewCapacity === 0}
              >
                {create.isPending ? (
                  <>
                    <Loader2 className="animate-spin" />
                    Création…
                  </>
                ) : (
                  <>
                    <CheckCircle2 />
                    Créer le classeur
                  </>
                )}
              </Button>
              {create.isError ? (
                <span className="text-destructive text-sm">{(create.error as Error).message}</span>
              ) : null}
            </div>
          </form>
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
  min,
}: {
  id: string
  label: string
  value: number
  onChange: (value: number) => void
  min: number
}) {
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="font-medium text-muted-foreground text-xs">
        {label}
      </label>
      <Input
        id={id}
        type="number"
        min={min}
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
