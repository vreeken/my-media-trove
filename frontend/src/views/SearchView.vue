<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { mediaService } from '@/services/media'
import { useWishlistStore } from '@/stores/wishlist'
import MediaDetailsModal from '@/components/media/MediaDetailsModal.vue'
import type { MediaSearchResult } from '@/types'

const router = useRouter()
const wishlistStore = useWishlistStore()

const searchQuery = ref('')
const searchType = ref<'all' | 'movie' | 'series' | 'album' | 'song'>('all')
const loading = ref(false)
const results = reactive<{
  movies_tv: MediaSearchResult[]
  albums: MediaSearchResult[]
  songs: MediaSearchResult[]
}>({
  movies_tv: [],
  albums: [],
  songs: [],
})

// Modal state
const selectedItem = ref<MediaSearchResult | null>(null)
const showModal = ref(false)
const addingToWishlist = ref(false)
const wishlistSuccess = ref(false)
const wishlistError = ref<string | null>(null)
const modalRef = ref<InstanceType<typeof MediaDetailsModal> | null>(null)

// Check if the selected item is already in wishlist
const isInWishlist = computed(() => {
  const item = modalRef.value?.displayItem || selectedItem.value
  if (!item?.external_id) return false
  return wishlistStore.isInWishlist(item.external_id)
})

// Fetch wishlist on mount to have data for checking
onMounted(async () => {
  // Only fetch if not already loaded
  if (wishlistStore.items.length === 0) {
    await wishlistStore.fetchWishlist()
  }
})

const debouncedSearch = useDebounceFn(async () => {
  if (!searchQuery.value.trim()) {
    results.movies_tv = []
    results.albums = []
    results.songs = []
    return
  }

  loading.value = true
  try {
    if (searchType.value === 'all') {
      const data = await mediaService.searchAll(searchQuery.value)
      results.movies_tv = data.movies_tv || []
      results.albums = data.albums || []
      results.songs = data.songs || []
    } else if (searchType.value === 'movie' || searchType.value === 'series') {
      const data = await mediaService.searchMovies(searchQuery.value, searchType.value)
      results.movies_tv = data
      results.albums = []
      results.songs = []
    } else if (searchType.value === 'album') {
      const data = await mediaService.searchAlbums(searchQuery.value)
      results.albums = data
      results.movies_tv = []
      results.songs = []
    } else if (searchType.value === 'song') {
      const data = await mediaService.searchSongs(searchQuery.value)
      results.songs = data
      results.movies_tv = []
      results.albums = []
    }
  } catch (e) {
    console.error('Search failed:', e)
  } finally {
    loading.value = false
  }
}, 400)

function openModal(item: MediaSearchResult) {
  selectedItem.value = item
  showModal.value = true
  wishlistSuccess.value = false
  wishlistError.value = null
}

function closeModal() {
  showModal.value = false
  selectedItem.value = null
  wishlistSuccess.value = false
  wishlistError.value = null
}

function addToCollection() {
  // Use the detailed item from the modal if available
  const item = modalRef.value?.displayItem || selectedItem.value
  if (!item) return

  router.push({
    name: 'add-media',
    query: {
      external_id: item.external_id,
      external_source: item.external_source,
      type: item.type,
      title: item.title,
      year: item.year?.toString() || '',
      poster_url: item.poster_url || '',
    }
  })
}

async function addToWishlist() {
  // Use the detailed item from the modal if available
  const item = modalRef.value?.displayItem || selectedItem.value
  if (!item) return

  // Check if already in wishlist
  if (item.external_id && wishlistStore.isInWishlist(item.external_id)) {
    wishlistError.value = 'This item is already in your wishlist'
    return
  }

  addingToWishlist.value = true
  wishlistError.value = null

  try {
    await wishlistStore.addItem({
      type: item.type,
      title: item.title,
      year: item.year,
      poster_url: item.poster_url,
      external_id: item.external_id,
      external_source: item.external_source,
      description: item.description,
      metadata: item.metadata,
    })
    wishlistSuccess.value = true
  } catch (e: unknown) {
    const err = e as { response?: { status?: number; data?: { message?: string } } }
    if (err.response?.status === 409) {
      // Item already in wishlist or collection
      wishlistError.value = err.response.data?.message || 'This item is already in your wishlist or collection'
      // Refresh wishlist to update local state
      await wishlistStore.fetchWishlist()
    } else {
      wishlistError.value = 'Failed to add to wishlist'
      console.error('Failed to add to wishlist:', e)
    }
  } finally {
    addingToWishlist.value = false
  }
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-white mb-2">Search Media</h1>
      <p class="text-dark-400">Find movies, shows, and music. Check streaming availability.</p>
    </div>

    <!-- Search Form -->
    <div class="space-y-3">
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="search"
          placeholder="Search for movies, TV shows, or music..."
          class="input pl-10"
        />
      </div>

      <!-- Type Filter -->
      <div class="flex gap-2 flex-wrap">
        <button
          v-for="type in [
            { value: 'all', label: 'All' },
            { value: 'movie', label: 'Movies' },
            { value: 'series', label: 'TV Shows' },
            { value: 'album', label: 'Albums' },
            { value: 'song', label: 'Songs' },
          ]"
          :key="type.value"
          @click="searchType = type.value as typeof searchType; debouncedSearch()"
          class="px-3 py-1.5 rounded-lg text-sm transition-colors"
          :class="searchType === type.value
            ? 'bg-primary-600 text-white'
            : 'bg-dark-700 text-dark-300 hover:bg-dark-600'"
        >
          {{ type.label }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Results -->
    <div v-else-if="results.movies_tv.length || results.albums.length || results.songs.length" class="space-y-8">
      <!-- Movies & TV -->
      <div v-if="results.movies_tv.length > 0">
        <h2 class="text-lg font-medium text-white mb-4">Movies & TV Shows</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <button
            v-for="item in results.movies_tv"
            :key="item.external_id"
            @click="openModal(item)"
            class="card-hover text-left"
          >
            <div class="aspect-[2/3] relative overflow-hidden rounded-lg">
              <img
                v-if="item.poster_url"
                :src="item.poster_url"
                :alt="item.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full bg-dark-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
              </div>
              <div class="absolute top-2 right-2">
                <span class="badge bg-primary-500/90 text-white text-xs">
                  {{ item.type === 'movie' ? 'Movie' : 'TV' }}
                </span>
              </div>
            </div>
            <div class="p-2">
              <h3 class="font-medium text-white text-sm line-clamp-2">{{ item.title }}</h3>
              <p v-if="item.year" class="text-xs text-dark-400 mt-1">{{ item.year }}</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Albums -->
      <div v-if="results.albums.length > 0">
        <h2 class="text-lg font-medium text-white mb-4">Albums</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <button
            v-for="item in results.albums"
            :key="item.external_id"
            @click="openModal(item)"
            class="card-hover text-left"
          >
            <div class="aspect-square relative overflow-hidden rounded-lg">
              <img
                v-if="item.poster_url"
                :src="item.poster_url"
                :alt="item.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full bg-dark-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                </svg>
              </div>
            </div>
            <div class="p-2">
              <h3 class="font-medium text-white text-sm line-clamp-2">{{ item.title }}</h3>
              <p v-if="item.metadata?.artist" class="text-xs text-dark-400 mt-1 line-clamp-1">{{ item.metadata.artist }}</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Songs -->
      <div v-if="results.songs.length > 0">
        <h2 class="text-lg font-medium text-white mb-4">Songs</h2>
        <div class="space-y-2">
          <button
            v-for="item in results.songs"
            :key="item.external_id"
            @click="openModal(item)"
            class="card p-3 flex items-center gap-3 w-full text-left hover:bg-dark-700/50 transition-colors"
          >
            <svg class="w-8 h-8 text-dark-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
            <div class="flex-1 min-w-0">
              <h3 class="font-medium text-white text-sm line-clamp-1">{{ item.title }}</h3>
              <p v-if="item.metadata?.artist" class="text-xs text-dark-400 line-clamp-1">{{ item.metadata.artist }}</p>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-else-if="searchQuery && !loading" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">No results found</h3>
      <p class="text-dark-400">Try a different search term</p>
    </div>

    <!-- Initial State -->
    <div v-else class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">Search for media</h3>
      <p class="text-dark-400">Find movies, TV shows, and music to add to your collection</p>
    </div>

    <!-- Media Details Modal (reusable component) -->
    <MediaDetailsModal
      ref="modalRef"
      :item="selectedItem"
      :show="showModal"
      @close="closeModal"
    >
      <template #actions="{ close }">
        <!-- Wishlist success message -->
        <div v-if="wishlistSuccess" class="p-3 bg-green-500/10 border border-green-500/20 rounded-lg text-center">
          <p class="text-green-400 text-sm">Added to wishlist!</p>
          <router-link :to="{ name: 'wishlist' }" class="text-primary-400 text-sm hover:text-primary-300">
            View Wishlist →
          </router-link>
        </div>

        <!-- Already in wishlist message -->
        <div v-else-if="isInWishlist" class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg text-center">
          <p class="text-blue-400 text-sm">Already in your wishlist</p>
          <router-link :to="{ name: 'wishlist' }" class="text-primary-400 text-sm hover:text-primary-300">
            View Wishlist →
          </router-link>
        </div>

        <!-- Error message -->
        <div v-if="wishlistError" class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-center">
          <p class="text-red-400 text-sm">{{ wishlistError }}</p>
        </div>

        <!-- Action buttons -->
        <div class="flex gap-3">
          <button @click="addToCollection" class="btn-primary flex-1">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add to Collection
          </button>
          <button
            v-if="!isInWishlist && !wishlistSuccess"
            @click="addToWishlist"
            :disabled="addingToWishlist"
            class="btn-secondary flex-1"
          >
            <svg v-if="addingToWishlist" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            Wishlist
          </button>
        </div>
        <button @click="close" class="btn-ghost w-full">Close</button>
      </template>
    </MediaDetailsModal>
  </div>
</template>
