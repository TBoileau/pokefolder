import {
  createRootRoute,
  createRoute,
  createRouter,
  Outlet,
} from '@tanstack/react-router'
import { TanStackRouterDevtools } from '@tanstack/react-router-devtools'

import { HomePage } from '@/pages/HomePage'

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

const routeTree = rootRoute.addChildren([indexRoute])

export const router = createRouter({ routeTree })

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
}
