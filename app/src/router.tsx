import { createRootRoute, createRoute, createRouter, Outlet } from '@tanstack/react-router'
import { TanStackRouterDevtools } from '@tanstack/react-router-devtools'
import { z } from 'zod'

import { CardsPage } from '@/pages/CardsPage'
import { CollectionCardPage } from '@/pages/CollectionCardPage'
import { CollectionPage } from '@/pages/CollectionPage'
import { HomePage } from '@/pages/HomePage'
import { SyncPage } from '@/pages/SyncPage'

const rootRoute = createRootRoute({
  component: () => (
    <>
      <Outlet />
      {import.meta.env.DEV && <TanStackRouterDevtools position="bottom-right" />}
    </>
  ),
})

const indexRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  component: HomePage,
})

const cardsSearchSchema = z.object({
  page: z.coerce.number().int().positive().catch(1),
  q: z.string().optional().catch(undefined),
  setId: z.string().optional().catch(undefined),
  language: z.enum(['fr', 'en']).optional().catch(undefined),
  variant: z
    .enum(['normal', 'reverse', 'holo', 'firstEdition', 'wPromo'])
    .optional()
    .catch(undefined),
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

const routeTree = rootRoute.addChildren([
  indexRoute,
  cardsRoute,
  syncRoute,
  collectionRoute,
  collectionCardRoute,
])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
