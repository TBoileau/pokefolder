export function HomePage() {
  return (
    <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-6 py-12">
      <div className="space-y-2">
        <h2 className="font-semibold text-3xl tracking-tight">Bienvenue dans pokefolder</h2>
        <p className="text-muted-foreground">
          Gestionnaire de collection Pokémon TCG. Utilise le menu en haut pour naviguer entre le
          catalogue, ta collection, tes classeurs, et la synchronisation TCGdex.
        </p>
      </div>
    </main>
  )
}
