<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const navItems = [
  { name: 'home', label: 'Dashboard', icon: 'home' },
  { name: 'collection', label: 'My Collection', icon: 'collection' },
  { name: 'search', label: 'Search', icon: 'search' },
  { name: 'add-media', label: 'Add Media', icon: 'plus' },
  { name: 'wishlist', label: 'Wishlist', icon: 'heart' },
]

const settingsItems = [
  { name: 'settings', label: 'Settings', icon: 'cog' },
  { name: 'locations', label: 'Locations', icon: 'location' },
  { name: 'tags', label: 'Tags', icon: 'tag' },
]

const currentRoute = computed(() => route.name)

function navigate(name: string) {
  router.push({ name })
}

async function logout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <aside class="fixed left-0 top-0 bottom-0 w-64 bg-dark-800 border-r border-dark-700 flex flex-col">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 h-16 border-b border-dark-700">
      <div class="flex items-center justify-center w-10 h-10">
        <img src="/assets/my_media_trove_logo_square.png" alt="My Media Trove Logo" class="w-10 h-10" />
      </div>
      <span class="font-semibold text-white">My Media Trove</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <button
        v-for="item in navItems"
        :key="item.name"
        @click="navigate(item.name)"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors"
        :class="currentRoute === item.name
          ? 'bg-primary-600 text-white'
          : 'text-dark-300 hover:bg-dark-700 hover:text-white'"
      >
        <!-- Icons -->
        <svg v-if="item.icon === 'home'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <svg v-else-if="item.icon === 'collection'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <svg v-else-if="item.icon === 'search'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <svg v-else-if="item.icon === 'plus'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <svg v-else-if="item.icon === 'heart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
        <span>{{ item.label }}</span>
      </button>

      <!-- Divider -->
      <div class="!my-4 border-t border-dark-700"></div>

      <!-- Settings Items -->
      <button
        v-for="item in settingsItems"
        :key="item.name"
        @click="navigate(item.name)"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors"
        :class="currentRoute === item.name
          ? 'bg-primary-600 text-white'
          : 'text-dark-300 hover:bg-dark-700 hover:text-white'"
      >
        <svg v-if="item.icon === 'cog'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg v-else-if="item.icon === 'location'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg v-else-if="item.icon === 'tag'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
        </svg>
        <span>{{ item.label }}</span>
      </button>
    </nav>

    <!-- User Section -->
    <div class="border-t border-dark-700 p-3">
      <div class="flex items-center gap-3 px-3 py-2">
        <div class="w-9 h-9 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
          {{ authStore.user?.name?.charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate">{{ authStore.user?.name }}</p>
          <p class="text-xs text-dark-400 truncate">{{ authStore.user?.email }}</p>
        </div>
        <button
          @click="logout"
          class="p-2 text-dark-400 hover:text-white rounded-lg hover:bg-dark-700 transition-colors"
          title="Logout"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>
