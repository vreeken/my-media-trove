<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { mediaService } from '@/services/media'
import type { MediaSearchResult, StreamingAvailability } from '@/types'

interface Props {
  /** The media item to display - can be a search result or wishlist item */
  item: MediaSearchResult | null
  /** Whether to show the modal */
  show: boolean
  /** Extra info to show in the header (like priority) */
  showPriority?: boolean
  /** Priority value if showing priority */
  priority?: number
}

const props = withDefaults(defineProps<Props>(), {
  showPriority: false,
  priority: 0,
})

const emit = defineEmits<{
  close: []
  priorityChange: [priority: number]
}>()

// Full details state
const itemDetails = ref<MediaSearchResult | null>(null)
const loadingDetails = ref(false)

// Streaming state
const streamingData = ref<StreamingAvailability | null>(null)
const loadingStreaming = ref(false)
const refreshingStreaming = ref(false)

// UI state
const showDetails = ref(false)

// Use detailed data if available, otherwise fall back to the item prop
const displayItem = computed(() => itemDetails.value || props.item)

// Check if we have additional details to show
const hasDetails = computed(() => {
  const item = displayItem.value
  if (!item) return false
  const m = item.metadata
  return item.description || m?.director || m?.actors || m?.runtime || m?.genre
})

// Watch for item changes and fetch data
watch(() => props.item, async (newItem) => {
  if (!newItem) {
    resetState()
    return
  }

  // Reset state for new item
  itemDetails.value = null
  streamingData.value = null
  showDetails.value = false

  if (newItem.external_source === 'omdb' && newItem.external_id) {
    // Fetch both details and streaming in parallel
    loadingDetails.value = true
    loadingStreaming.value = true

    const [detailsResult, streamingResult] = await Promise.allSettled([
      mediaService.getMovieDetails(newItem.external_id),
      mediaService.getStreamingAvailability(newItem.external_id)
    ])

    if (detailsResult.status === 'fulfilled') {
      itemDetails.value = detailsResult.value
    }

    if (streamingResult.status === 'fulfilled') {
      streamingData.value = streamingResult.value
    }

    loadingDetails.value = false
    loadingStreaming.value = false
  } else if (newItem.external_source === 'musicbrainz' && newItem.external_id) {
    loadingDetails.value = true
    try {
      itemDetails.value = await mediaService.getAlbumDetails(newItem.external_id)
    } catch (e) {
      console.error('Failed to load album details:', e)
    } finally {
      loadingDetails.value = false
    }
  }
}, { immediate: true })

async function refreshStreamingData() {
  const item = displayItem.value
  if (!item?.external_id) return

  refreshingStreaming.value = true
  try {
    streamingData.value = await mediaService.refreshStreamingAvailability(item.external_id)
  } catch (e) {
    console.error('Failed to refresh streaming info:', e)
  } finally {
    refreshingStreaming.value = false
  }
}

function resetState() {
  itemDetails.value = null
  streamingData.value = null
  showDetails.value = false
  loadingDetails.value = false
  loadingStreaming.value = false
  refreshingStreaming.value = false
}

function handleClose() {
  emit('close')
}

function handlePriorityClick(value: number, event: Event) {
  event.stopPropagation()
  emit('priorityChange', value)
}

// Expose data for parent components
defineExpose({
  displayItem,
  streamingData,
  loadingDetails,
  loadingStreaming,
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show && item"
      class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/50"
      @click.self="handleClose"
    >
      <div class="card w-full max-w-lg max-h-[90vh] overflow-y-auto animate-slide-up md:animate-fade-in">
        <div class="p-6 space-y-4">
          <!-- Header -->
          <div class="flex items-start gap-4">
            <img
              v-if="displayItem?.poster_url"
              :src="displayItem.poster_url"
              :alt="displayItem.title"
              class="w-20 h-28 object-cover rounded-lg flex-shrink-0"
            />
            <div v-else class="w-20 h-28 bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="text-lg font-semibold text-white">{{ displayItem?.title }}</h2>
              <div class="flex flex-wrap items-center gap-2 text-sm text-dark-400 mt-1">
                <span v-if="displayItem?.year">{{ displayItem.year }}</span>
                <span v-if="displayItem?.metadata?.runtime">• {{ displayItem.metadata.runtime }}</span>
                <span v-if="displayItem?.metadata?.rated" class="px-1.5 py-0.5 bg-dark-600 rounded text-xs">{{ displayItem.metadata.rated }}</span>
              </div>
              <div v-if="displayItem?.metadata?.genre" class="text-sm text-dark-300 mt-1 line-clamp-1">
                {{ displayItem.metadata.genre }}
              </div>
              <!-- IMDb Rating -->
              <div v-if="displayItem?.metadata?.imdb_rating" class="flex items-center gap-1 mt-2">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-sm text-white font-medium">{{ displayItem.metadata.imdb_rating }}</span>
                <span class="text-xs text-dark-500">/10</span>
                <span v-if="displayItem?.metadata?.imdb_votes" class="text-xs text-dark-500 ml-1">({{ displayItem.metadata.imdb_votes }})</span>
              </div>
              <!-- Loading indicator for details -->
              <div v-else-if="loadingDetails" class="flex items-center gap-2 mt-2">
                <div class="animate-spin w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full"></div>
                <span class="text-xs text-dark-400">Loading details...</span>
              </div>
              <!-- Priority stars (optional) -->
              <div v-if="showPriority" class="flex items-center gap-1 mt-2">
                <button
                  v-for="i in 5"
                  :key="i"
                  @click="handlePriorityClick(i, $event)"
                  class="w-4 h-4"
                  :class="i <= priority ? 'text-yellow-400' : 'text-dark-600 hover:text-dark-500'"
                >
                  <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Expandable Details Section -->
          <div v-if="hasDetails">
            <button
              @click="showDetails = !showDetails"
              class="flex items-center justify-between w-full p-3 bg-dark-700/50 rounded-lg text-left hover:bg-dark-700 transition-colors"
            >
              <span class="text-sm font-medium text-white">Details</span>
              <svg
                class="w-5 h-5 text-dark-400 transition-transform"
                :class="{ 'rotate-180': showDetails }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <div v-if="showDetails" class="mt-3 space-y-3 animate-fade-in">
              <!-- Description/Plot -->
              <div v-if="displayItem?.description">
                <p class="text-sm text-dark-300 leading-relaxed">
                  {{ displayItem.description }}
                </p>
              </div>

              <!-- Director -->
              <div v-if="displayItem?.metadata?.director" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Director:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.director }}</span>
              </div>

              <!-- Writer -->
              <div v-if="displayItem?.metadata?.writer" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Writer:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.writer }}</span>
              </div>

              <!-- Actors/Cast -->
              <div v-if="displayItem?.metadata?.actors" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Cast:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.actors }}</span>
              </div>

              <!-- Awards -->
              <div v-if="displayItem?.metadata?.awards" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Awards:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.awards }}</span>
              </div>

              <!-- Box Office -->
              <div v-if="displayItem?.metadata?.box_office" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Box Office:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.box_office }}</span>
              </div>

              <!-- Language -->
              <div v-if="displayItem?.metadata?.language" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Language:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.language }}</span>
              </div>

              <!-- Country -->
              <div v-if="displayItem?.metadata?.country" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Country:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.country }}</span>
              </div>

              <!-- Total Seasons (for TV shows) -->
              <div v-if="displayItem?.metadata?.total_seasons" class="flex gap-2">
                <span class="text-sm text-dark-500 flex-shrink-0">Seasons:</span>
                <span class="text-sm text-dark-300">{{ displayItem.metadata.total_seasons }}</span>
              </div>
            </div>
          </div>

          <!-- Loading streaming info -->
          <div v-if="loadingStreaming" class="flex items-center justify-center py-8">
            <div class="animate-spin w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full"></div>
          </div>

          <!-- Streaming options -->
          <div v-else-if="streamingData" class="space-y-4">
            <!-- Not configured message -->
            <div v-if="streamingData.configured === false" class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
              <p class="text-yellow-400 text-sm">{{ streamingData.message }}</p>
            </div>

            <!-- Error message -->
            <div v-else-if="streamingData.error" class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
              <p class="text-red-400 text-sm">{{ streamingData.message }}</p>
            </div>

            <!-- Cache Info & Refresh Button -->
            <div v-if="streamingData.cached" class="flex items-center justify-between p-3 bg-dark-700/50 rounded-lg">
              <div class="text-sm">
                <span class="text-dark-400">Stream/rent/buy data from </span>
                <span :class="streamingData.cached.is_stale ? 'text-yellow-400' : 'text-dark-300'">
                  {{ streamingData.cached.age }}
                </span>
                <span v-if="streamingData.cached.is_stale" class="text-yellow-400 ml-1">(stale)</span>
              </div>
              <button
                @click="refreshStreamingData"
                :disabled="refreshingStreaming"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-dark-600 hover:bg-dark-500 rounded-lg text-dark-200 transition-colors disabled:opacity-50"
              >
                <svg
                  :class="{ 'animate-spin': refreshingStreaming }"
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ refreshingStreaming ? 'Refreshing...' : 'Refresh' }}
              </button>
            </div>

            <!-- Free streaming options -->
            <div v-if="streamingData.free?.length || streamingData.mock_data?.free?.length">
              <h3 class="text-sm font-medium text-white mb-2 flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                Free
              </h3>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="option in (streamingData.free || streamingData.mock_data?.free)"
                  :key="`free-${option.service}`"
                  :href="option.video_link || option.link"
                  target="_blank"
                  class="flex items-center gap-2 px-3 py-2 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-white hover:bg-green-500/20 transition-colors"
                >
                  <img v-if="option.service_logo" :src="option.service_logo" :alt="option.service_name" class="w-5 h-5" />
                  {{ option.service_name }}
                  <span v-if="option.quality" class="text-xs text-dark-400 uppercase">{{ option.quality }}</span>
                </a>
              </div>
            </div>

            <!-- Subscription streaming -->
            <div v-if="streamingData.streaming?.length || streamingData.mock_data?.streaming?.length">
              <h3 class="text-sm font-medium text-white mb-2">Stream</h3>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="option in (streamingData.streaming || streamingData.mock_data?.streaming)"
                  :key="`stream-${option.service}`"
                  :href="option.video_link || option.link"
                  target="_blank"
                  class="flex items-center gap-2 px-3 py-2 bg-dark-700 rounded-lg text-sm text-white hover:bg-dark-600 transition-colors"
                >
                  <img v-if="option.service_logo" :src="option.service_logo" :alt="option.service_name" class="w-5 h-5" />
                  {{ option.service_name }}
                  <span v-if="option.addon" class="text-xs text-dark-400">+ {{ option.addon.name }}</span>
                  <span v-if="option.quality" class="text-xs text-dark-400 uppercase">{{ option.quality }}</span>
                  <span v-if="option.expires_soon" class="text-xs text-yellow-400">Leaving soon</span>
                </a>
              </div>
            </div>

            <div v-if="streamingData.rent?.length || streamingData.mock_data?.rent?.length">
              <h3 class="text-sm font-medium text-white mb-2">Rent</h3>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="option in (streamingData.rent || streamingData.mock_data?.rent)"
                  :key="`rent-${option.service}`"
                  :href="option.link"
                  target="_blank"
                  class="flex items-center gap-2 px-3 py-2 bg-dark-700 rounded-lg text-sm text-white hover:bg-dark-600 transition-colors"
                >
                  <img v-if="option.service_logo" :src="option.service_logo" :alt="option.service_name" class="w-5 h-5" />
                  {{ option.service_name }}
                  <span v-if="option.price_formatted" class="text-dark-400">{{ option.price_formatted }}</span>
                  <span v-else-if="option.price" class="text-dark-400">{{ option.currency }}{{ option.price }}</span>
                </a>
              </div>
            </div>

            <div v-if="streamingData.buy?.length || streamingData.mock_data?.buy?.length">
              <h3 class="text-sm font-medium text-white mb-2">Buy</h3>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="option in (streamingData.buy || streamingData.mock_data?.buy)"
                  :key="`buy-${option.service}`"
                  :href="option.affiliate_link || option.link"
                  target="_blank"
                  class="flex items-center gap-2 px-3 py-2 bg-dark-700 rounded-lg text-sm text-white hover:bg-dark-600 transition-colors"
                >
                  <img v-if="option.service_logo" :src="option.service_logo" :alt="option.service_name" class="w-5 h-5" />
                  {{ option.service_name }}
                  <span v-if="option.price_formatted" class="text-dark-400">{{ option.price_formatted }}</span>
                  <span v-else-if="option.price" class="text-dark-400">{{ option.currency }}{{ option.price }}</span>
                </a>
              </div>
            </div>

            <!-- No streaming available -->
            <div v-if="streamingData.configured !== false && !streamingData.error && !streamingData.streaming?.length && !streamingData.rent?.length && !streamingData.buy?.length && !streamingData.free?.length && !streamingData.mock_data" class="p-4 bg-dark-700/50 rounded-lg text-center">
              <p class="text-dark-400 text-sm">No streaming options found for your region</p>
            </div>
          </div>

          <!-- No streaming data for non-OMDB items -->
          <div v-else-if="!loadingStreaming && displayItem?.external_source !== 'omdb'" class="p-4 bg-dark-700/50 rounded-lg text-center">
            <p class="text-dark-400 text-sm">Streaming data is only available for movies and TV shows</p>
          </div>

          <!-- Action buttons slot -->
          <div class="space-y-3 pt-2">
            <slot name="actions" :item="displayItem" :close="handleClose">
              <!-- Default close button if no slot provided -->
              <button @click="handleClose" class="btn-ghost w-full">Close</button>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
