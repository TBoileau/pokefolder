import { createRootRoute, createRoute, createRouter, Outlet } from '@tanstack/react-router'
import { TanStackRouterDevtools } from '@tanstack/react-router-devtools'
import { z } from 'zod'

import { Header } from '@/components/Header'
import { BinderCreatePage } from '@/pages/BinderCreatePage'
import { BinderEditPage } from '@/pages/BinderEditPage'
import { BindersPage } from '@/pages/BindersPage'
import { BinderViewPage } from '@/pages/BinderViewPage'
import { CardsPage } from '@/pages/CardsPage'
import { CollectionAddPage } from '@/pages/CollectionAddPage'
import { CollectionCardPage } from '@/pages/CollectionCardPage'
import { CollectionPage } from '@/pages/CollectionPage'
import { HomePage } from '@/pages/HomePage'
import { SyncPage } from '@/pages/SyncPage'

const rootRoute = createRootRoute({
  component: () => (
    <div className="flex min-h-svh flex-col">
      <Header />
      <Outlet />
      {import.meta.env.DEV && <TanStackRouterDevtools position="bottom-right" />}
    </div>
  ),
})

const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  component: HomePage,
})

const cardsSearchSchema = z.object({
  serie: z.string().optional().catch(undefined),
  set: z.string().optional().catch(undefined),
  language: z.string().optional().catch(undefined),
  variants: z.array(z.string()).optional().catch(undefined),
  rarities: z.array(z.string()).optional().catch(undefined),
  q: z.string().optional().catch(undefined),
  master: z.coerce.boolean().optional().catch(undefined),
})

export type CardsSearch = z.infer<typeof cardsSearchSchema>

const cardsRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/cards',
  component: CardsPage,
  validateSearch: cardsSearchSchema,
})

const syncRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/sync',
  component: SyncPage,
})

const collectionSearchSchema = z.object({
  page: z.coerce.number().int().positive().catch(1),
  q: z.string().optional().catch(undefined),
  setId: z.string().optional().catch(undefined),
  language: z.enum(['fr', 'en']).optional().catch(undefined),
  variant: z
    .enum(['normal', 'reverse', 'holo', 'firstEdition', 'wPromo'])
    .optional()
    .catch(undefined),
  condition: z.enum(['M', 'NM', 'EX', 'GD', 'LP', 'PL', 'HP', 'DMG']).optional().catch(undefined),
})

const collectionRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/collection',
  component: CollectionPage,
  validateSearch: collectionSearchSchema,
})

const collectionCardRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/collection/cards/$cardId',
  component: CollectionCardPage,
})

const collectionAddRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/collection/add',
  component: CollectionAddPage,
})

const bindersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/binders',
  component: BindersPage,
})

const binderCreateRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/binders/new',
  component: BinderCreatePage,
})

const binderViewRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/binders/$binderId',
  component: BinderViewPage,
})

const binderEditRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/binders/$binderId/edit',
  component: BinderEditPage,
})

const routeTree = rootRoute.addChildren([
  indexRoute,
  cardsRoute,
  syncRoute,
  collectionRoute,
  collectionCardRoute,
  collectionAddRoute,
  bindersRoute,
  binderCreateRoute,
  binderViewRoute,
  binderEditRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
