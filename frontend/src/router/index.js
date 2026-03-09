import { defineRouter } from '#q-app/wrappers'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'
import { useAuthStore } from 'src/stores/auth-store'
import routes from './routes'

function getDefaultRouteByRole(role) {
  if (role === 'admin') {
    return '/admin/offices'
  }

  if (role === 'staff') {
    return '/staff/queue'
  }

  return '/queue'
}

export default defineRouter(function ({ store }) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE),
  })

  const authStore = useAuthStore(store)

  router.beforeEach(async (to) => {
    await authStore.initialize()

    if (to.meta.guestOnly && authStore.isAuthenticated) {
      return getDefaultRouteByRole(authStore.role)
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return {
        path: '/login',
        query: {
          redirect: to.fullPath,
        },
      }
    }

    const allowedRoles = to.meta.roles

    if (
      Array.isArray(allowedRoles) &&
      authStore.isAuthenticated &&
      authStore.role &&
      !allowedRoles.includes(authStore.role)
    ) {
      return getDefaultRouteByRole(authStore.role)
    }

    return true
  })

  return router
})
