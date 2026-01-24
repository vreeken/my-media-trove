import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Auth routes
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { guest: true }
    },
    {
      path: '/auth/callback',
      name: 'auth-callback',
      component: () => import('@/views/auth/AuthCallbackView.vue'),
      meta: { guest: true }
    },

    // App routes (protected)
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/collection',
      name: 'collection',
      component: () => import('@/views/CollectionView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/collection/:id',
      name: 'media-detail',
      component: () => import('@/views/MediaDetailView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('@/views/SearchView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/add',
      name: 'add-media',
      component: () => import('@/views/AddMediaView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/wishlist',
      name: 'wishlist',
      component: () => import('@/views/WishlistView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/SettingsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/settings/locations',
      name: 'locations',
      component: () => import('@/views/settings/LocationsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/settings/tags',
      name: 'tags',
      component: () => import('@/views/settings/TagsView.vue'),
      meta: { requiresAuth: true }
    },

    // Catch-all
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // Wait for auth check to complete
  if (!authStore.initialized) {
    await authStore.checkAuth()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'home' })
  } else {
    next()
  }
})

export default router
