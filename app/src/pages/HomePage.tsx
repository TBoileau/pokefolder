import { Link } from '@tanstack/react-router'
import { Library } from 'lucide-react'

import { Button } from '@/components/ui/button'

export function HomePage() {
  return (
    <div className="flex min-h-svh flex-col">
      <header className="border-b">
        <div className="mx-auto flex max-w-5xl items-center gap-3 px-6 py-4">
          <Library className="size-6 text-primary" />
          <h1 className="font-semibold text-lg tracking-tight">pokefolder</h1>
        </div>
      </header>

      <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-6 py-12">
        <div className="space-y-2">
          <h2 className="font-semibold text-3xl tracking-tight">Bienvenue dans pokefolder</h2>
          <p className="text-muted-foreground">
            Gestionnaire de collection Pokémon TCG. Le squelette est branché — les fonctionnalités
            arriveront slice par slice.
          </p>
        </div>

        <div className="flex flex-wrap gap-3">
          <Button asChild>
            <Link to="/cards" search={{ page: 1 }}>
              Parcourir le catalogue
            </Link>
          </Button>
        </div>
      </main>
    </div>
  )
}
