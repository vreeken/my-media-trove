<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMediaStore } from '@/stores/media'

const router = useRouter()
const authStore = useAuthStore()
const mediaStore = useMediaStore()

onMounted(async () => {
  await mediaStore.fetchStats()
})

function navigateTo(route: string) {
  router.push({ name: route })
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <!-- Welcome Section -->
    <div class="card p-6">
      <h1 class="text-2xl font-bold text-white mb-2">
        Welcome back, {{ authStore.user?.name?.split(' ')[0] }}!
      </h1>
      <p class="text-dark-400">Manage your personal media collection</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="card p-4">
        <p class="text-dark-400 text-sm">Total Items</p>
        <p class="text-2xl font-bold text-white mt-1">
          {{ mediaStore.stats?.total ?? 0 }}
        </p>
      </div>
      <div class="card p-4">
        <p class="text-dark-400 text-sm">Movies</p>
        <p class="text-2xl font-bold text-white mt-1">
          {{ mediaStore.stats?.by_type?.movie?.count ?? 0 }}
        </p>
      </div>
      <div class="card p-4">
        <p class="text-dark-400 text-sm">TV Shows</p>
        <p class="text-2xl font-bold text-white mt-1">
          {{ mediaStore.stats?.by_type?.tv_show?.count ?? 0 }}
        </p>
      </div>
      <div class="card p-4">
        <p class="text-dark-400 text-sm">Music</p>
        <p class="text-2xl font-bold text-white mt-1">
          {{ (mediaStore.stats?.by_type?.album?.count ?? 0) + (mediaStore.stats?.by_type?.song?.count ?? 0) }}
        </p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="space-y-3">
      <h2 class="text-lg font-semibold text-white">Quick Actions</h2>
      <div class="grid grid-cols-2 gap-3">
        <button
          @click="navigateTo('add-media')"
          class="card-hover p-4 text-left"
        >
          <div class="w-10 h-10 rounded-lg bg-primary-600/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <p class="font-medium text-white">Add Media</p>
          <p class="text-sm text-dark-400 mt-1">Search and add to collection</p>
        </button>

        <button
          @click="navigateTo('collection')"
          class="card-hover p-4 text-left"
        >
          <div class="w-10 h-10 rounded-lg bg-green-600/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <p class="font-medium text-white">Browse Collection</p>
          <p class="text-sm text-dark-400 mt-1">View all your media</p>
        </button>

        <button
          @click="navigateTo('wishlist')"
          class="card-hover p-4 text-left"
        >
          <div class="w-10 h-10 rounded-lg bg-red-600/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
          <p class="font-medium text-white">Wishlist</p>
          <p class="text-sm text-dark-400 mt-1">Media you want to get</p>
        </button>

        <button
          @click="navigateTo('search')"
          class="card-hover p-4 text-left"
        >
          <div class="w-10 h-10 rounded-lg bg-purple-600/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <p class="font-medium text-white">Search</p>
          <p class="text-sm text-dark-400 mt-1">Find streaming options</p>
        </button>
      </div>
    </div>
  </div>
</template>
