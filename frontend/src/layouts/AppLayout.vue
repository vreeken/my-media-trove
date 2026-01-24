<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MobileNav from '@/components/navigation/MobileNav.vue'
import DesktopSidebar from '@/components/navigation/DesktopSidebar.vue'

const route = useRoute()

const pageTitle = computed(() => {
  const titles: Record<string, string> = {
    'home': 'Dashboard',
    'collection': 'My Collection',
    'search': 'Search',
    'add-media': 'Add Media',
    'wishlist': 'Wishlist',
    'settings': 'Settings',
    'locations': 'Locations',
    'tags': 'Tags',
  }
  return titles[route.name as string] || 'My Media Trove'
})
</script>

<template>
  <div class="min-h-screen bg-dark-900">
    <!-- Desktop Sidebar -->
    <DesktopSidebar class="hidden md:flex" />

    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen">
      <!-- Top Bar (mobile) -->
      <header class="sticky top-0 z-40 bg-dark-900/95 backdrop-blur-lg border-b border-dark-800 md:hidden">
        <div class="flex items-center justify-between px-4 h-14">
          <h1 class="text-lg font-semibold text-white">{{ pageTitle }}</h1>
        </div>
      </header>

      <!-- Page Content -->
      <div class="page-container">
        <slot />
      </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <MobileNav class="md:hidden" />
  </div>
</template>
