<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '@/stores/wishlist'
import { useLocationStore } from '@/stores/locations'
import MediaDetailsModal from '@/components/media/MediaDetailsModal.vue'
import type { WishlistItem, MediaSearchResult } from '@/types'

const router = useRouter()
const wishlistStore = useWishlistStore()
const locationStore = useLocationStore()

// Move to collection modal
const selectedItem = ref<WishlistItem | null>(null)
const showMoveModal = ref(false)
const moveForm = ref({
  formats: [] as string[],
  location_id: '',
  rating: 0,
})

// Streaming info modal
const streamingItem = ref<WishlistItem | null>(null)
const showStreamingModal = ref(false)

onMounted(async () => {
  await Promise.all([
    wishlistStore.fetchWishlist(),
    locationStore.fetchLocations(),
  ])
})

function openStreamingModal(item: WishlistItem) {
  streamingItem.value = item
  showStreamingModal.value = true
}

function closeStreamingModal() {
  showStreamingModal.value = false
  streamingItem.value = null
}

function addToCollection() {
  if (!streamingItem.value) return

  // Close streaming modal and open move modal
  const item = streamingItem.value
  closeStreamingModal()
  openMoveModal(item)
}

function goToAddMedia() {
  if (!streamingItem.value) return

  const item = streamingItem.value
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

function openMoveModal(item: WishlistItem) {
  selectedItem.value = item
  moveForm.value = { formats: [], location_id: '', rating: 0 }
  showMoveModal.value = true
}

async function moveToCollection() {
  if (!selectedItem.value) return

  await wishlistStore.moveToCollection(selectedItem.value.id, {
    formats: moveForm.value.formats.length ? moveForm.value.formats : undefined,
    location_id: moveForm.value.location_id || undefined,
    rating: moveForm.value.rating || undefined,
  })

  showMoveModal.value = false
  selectedItem.value = null
}

async function removeItem(id: string) {
  await wishlistStore.removeItem(id)
  closeStreamingModal()
}

async function updatePriority(priority: number) {
  if (!streamingItem.value) return
  await wishlistStore.updateItem(streamingItem.value.id, { priority })
  // Update local ref to reflect change
  streamingItem.value = { ...streamingItem.value, priority }
}

async function updateItemPriority(item: WishlistItem, priority: number, event: Event) {
  event.stopPropagation()
  await wishlistStore.updateItem(item.id, { priority })
}

// Convert WishlistItem to MediaSearchResult format for the modal
function toMediaSearchResult(item: WishlistItem): MediaSearchResult {
  return {
    external_id: item.external_id || '',
    external_source: item.external_source || '',
    title: item.title,
    year: item.year,
    type: item.type,
    poster_url: item.poster_url,
    metadata: item.metadata,
  }
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-white mb-2">Wishlist</h1>
      <p class="text-dark-400">Media you want to add to your collection</p>
    </div>

    <!-- Loading -->
    <div v-if="wishlistStore.loading && wishlistStore.items.length === 0" class="flex items-center justify-center py-12">
      <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="wishlistStore.items.length === 0" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">Wishlist is empty</h3>
      <p class="text-dark-400 mb-4">Search for media and add items to your wishlist</p>
      <router-link to="/search" class="btn-primary">Search Media</router-link>
    </div>

    <!-- Wishlist Items -->
    <div v-else class="space-y-3">
      <button
        v-for="item in wishlistStore.items"
        :key="item.id"
        @click="openStreamingModal(item)"
        class="card p-4 flex gap-4 w-full text-left hover:bg-dark-700/50 transition-colors"
      >
        <!-- Poster -->
        <img
          v-if="item.poster_url"
          :src="item.poster_url"
          :alt="item.title"
          class="w-16 h-24 object-cover rounded-lg flex-shrink-0"
        />
        <div v-else class="w-16 h-24 bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-6 h-6 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
          </svg>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div>
              <span class="badge-primary text-xs">{{ item.type_label }}</span>
              <h3 class="font-medium text-white mt-1 line-clamp-1">{{ item.title }}</h3>
              <p v-if="item.year" class="text-sm text-dark-400">{{ item.year }}</p>
            </div>
          </div>

          <!-- Priority -->
          <div class="flex items-center gap-1 mt-2">
            <span class="text-xs text-dark-400 mr-1">Priority:</span>
            <button
              v-for="i in 5"
              :key="i"
              @click="updateItemPriority(item, i, $event)"
              class="w-4 h-4"
              :class="i <= item.priority ? 'text-yellow-400' : 'text-dark-600'"
            >
              <svg fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Quick actions hint -->
        <div class="flex items-center text-dark-500">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>
      </button>
    </div>

    <!-- Media Details Modal (reusable component) -->
    <MediaDetailsModal
      :item="streamingItem ? toMediaSearchResult(streamingItem) : null"
      :show="showStreamingModal"
      :show-priority="true"
      :priority="streamingItem?.priority || 0"
      @close="closeStreamingModal"
      @priority-change="updatePriority"
    >
      <template #actions="{ close }">
        <div class="flex gap-3">
          <button @click="addToCollection" class="btn-primary flex-1">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Got it!
          </button>
          <button @click="goToAddMedia" class="btn-secondary flex-1">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Full Add
          </button>
        </div>
        <div class="flex gap-3">
          <button @click="removeItem(streamingItem!.id)" class="btn-ghost flex-1 text-red-400 hover:bg-red-500/10">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Remove from Wishlist
          </button>
        </div>
        <button @click="close" class="btn-ghost w-full">Close</button>
      </template>
    </MediaDetailsModal>

    <!-- Move to Collection Modal -->
    <Teleport to="body">
      <div
        v-if="showMoveModal && selectedItem"
        class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/50"
        @click.self="showMoveModal = false"
      >
        <div class="card w-full max-w-md animate-slide-up md:animate-fade-in">
          <div class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">Add to Collection</h2>
            <p class="text-dark-400">Moving "{{ selectedItem.title }}" to your collection</p>

            <!-- Location -->
            <div>
              <label class="text-sm font-medium text-white mb-2 block">Location</label>
              <select v-model="moveForm.location_id" class="input">
                <option value="">No location</option>
                <option v-for="loc in locationStore.locations" :key="loc.id" :value="loc.id">
                  {{ loc.name }}
                </option>
              </select>
            </div>

            <!-- Rating -->
            <div>
              <p class="text-sm font-medium text-white mb-2">Rating</p>
              <div class="flex gap-1">
                <button
                  v-for="i in 10"
                  :key="i"
                  @click="moveForm.rating = i"
                  class="w-6 h-6 transition-colors"
                  :class="i <= moveForm.rating ? 'text-yellow-400' : 'text-dark-600'"
                >
                  <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <button @click="moveToCollection" class="btn-primary flex-1">Add to Collection</button>
              <button @click="showMoveModal = false" class="btn-secondary flex-1">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
