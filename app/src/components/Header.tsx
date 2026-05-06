import { Link, useRouterState } from '@tanstack/react-router'
import { Library } from 'lucide-react'

const NAV = [
  { to: '/cards', label: 'Catalogue', search: { page: 1 } },
  { to: '/collection', label: 'Collection', search: { page: 1 } },
  { to: '/binders', label: 'Classeurs' },
  { to: '/sync', label: 'Sync' },
] as const

export function Header() {
  const { location } = useRouterState()
  const pathname = location.pathname

  return (
    <header className="border-b">
      <div className="mx-auto flex max-w-6xl items-center gap-6 px-6 py-4">
        <Link to="/" className="flex items-center gap-3 text-foreground">
          <Library className="size-6 text-primary" />
          <span className="font-semibold text-lg tracking-tight">pokefolder</span>
        </Link>
        <nav className="flex flex-wrap items-center gap-1 sm:gap-3">
          {NAV.map((item) => {
            const active = pathname === item.to || pathname.startsWith(`${item.to}/`)
            return (
              <Link
                key={item.to}
                to={item.to}
                {...('search' in item ? { search: item.search } : {})}
                className={`rounded px-2 py-1 font-medium text-sm transition-colors ${
                  active
                    ? 'text-primary underline decoration-2 decoration-primary underline-offset-4'
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {item.label}
              </Link>
            )
          })}
        </nav>
      </div>
    </header>
  )
}
