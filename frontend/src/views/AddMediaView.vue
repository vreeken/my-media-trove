<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { mediaService } from '@/services/media'
import { barcodeService } from '@/services/barcode'
import { useMediaStore } from '@/stores/media'
import { useTagStore } from '@/stores/tags'
import { useLocationStore } from '@/stores/locations'
import BarcodeScanner from '@/components/barcode/BarcodeScanner.vue'
import type { MediaSearchResult, MediaType, DigitalPlatformsResponse, DigitalPlatform, BarcodeMatch, UpcItemDbProduct } from '@/types'

const router = useRouter()
const route = useRoute()
const mediaStore = useMediaStore()
const tagStore = useTagStore()
const locationStore = useLocationStore()

const step = ref<'search' | 'details' | 'barcode-results' | 'box-set'>('search')
const searchQuery = ref('')
const searchType = ref<'movie' | 'series' | 'album' | 'song'>('movie')
const loading = ref(false)
const saving = ref(false)
const searchResults = ref<MediaSearchResult[]>([])
const selectedResult = ref<MediaSearchResult | null>(null)
const isCustom = ref(false)
const types = ref<MediaType[]>([])
const showDetails = ref(false)

// Barcode scanning state
const barcodeScanAvailable = ref(false)
const showBarcodeScanner = ref(false)
const currentBarcode = ref<string | null>(null)
const barcodeMatches = ref<BarcodeMatch[]>([])
const upcProduct = ref<UpcItemDbProduct | null>(null)
const barcodeLoading = ref(false)
const isBoxSet = ref(false)
const franchiseName = ref<string | null>(null)
const pendingMediaItemId = ref<string | null>(null) // For creating barcode association after save

// Digital platform state
const digitalPlatforms = ref<DigitalPlatformsResponse | null>(null)
const selectedPlatformRequiresPath = computed(() => {
  if (!form.is_digital || !form.digital_platform || !digitalPlatforms.value) return false
  const platform = digitalPlatforms.value.flat.find((p: DigitalPlatform) => p.id === form.digital_platform)
  return platform?.requires_path ?? false
})

// Check if we have additional details to show
const hasDetails = computed(() => {
  const m = form.metadata
  return form.description || m?.director || m?.actors || m?.runtime || m?.genre
})

const form = reactive({
  type: 'movie',
  title: '',
  year: undefined as number | undefined,
  description: '',
  poster_url: '',
  external_id: '',
  external_source: '',
  is_custom: false,
  formats: [] as string[],
  rating: 0,
  notes: '',
  location_id: '',
  tag_ids: [] as string[],
  metadata: {} as Record<string, unknown>,
  // Digital vs Physical
  is_digital: false,
  digital_platform: '',
  digital_path: '',
})

// Clear digital fields when toggling off
watch(() => form.is_digital, (isDigital) => {
  if (!isDigital) {
    form.digital_platform = ''
    form.digital_path = ''
  }
})

// Clear path when platform doesn't require it
watch(() => form.digital_platform, () => {
  if (!selectedPlatformRequiresPath.value) {
    form.digital_path = ''
  }
})

const availableFormats = ref<Record<string, string>>({})

onMounted(async () => {
  await Promise.all([
    mediaStore.fetchTypes(),
    tagStore.fetchTags(),
    locationStore.fetchLocations(),
    loadDigitalPlatforms(),
    checkBarcodeScanAvailable(),
  ])
  types.value = mediaStore.types
  updateAvailableFormats()

  // Check if we have query params from SearchView
  const query = route.query
  if (query.external_id && query.external_source) {
    await loadFromQueryParams()
  }
})

async function checkBarcodeScanAvailable() {
  barcodeScanAvailable.value = await barcodeService.isAvailable()
}

async function loadDigitalPlatforms() {
  try {
    digitalPlatforms.value = await mediaService.getDigitalPlatforms()
  } catch (e) {
    console.error('Failed to load digital platforms:', e)
  }
}

async function loadFromQueryParams() {
  const query = route.query
  loading.value = true

  try {
    let details: MediaSearchResult | null = null

    // Fetch full details from the API
    if (query.external_source === 'omdb') {
      details = await mediaService.getMovieDetails(query.external_id as string)
    } else if (query.external_source === 'musicbrainz' && (query.type === 'album' || query.type === 'song')) {
      if (query.type === 'album') {
        details = await mediaService.getAlbumDetails(query.external_id as string)
      } else {
        // For songs, use the query params directly since there's no separate details endpoint
        details = {
          external_id: query.external_id as string,
          external_source: query.external_source as string,
          type: query.type as string,
          title: query.title as string,
          year: query.year ? parseInt(query.year as string) : undefined,
          poster_url: query.poster_url as string || undefined,
        }
      }
    }

    if (details) {
      selectedResult.value = details
      form.type = details.type
      form.title = details.title
      form.year = details.year
      form.description = details.description || ''
      form.poster_url = details.poster_url || ''
      form.external_id = details.external_id
      form.external_source = details.external_source
      form.is_custom = false
      form.metadata = details.metadata || {}

      updateAvailableFormats()
      step.value = 'details'
    }
  } catch (e) {
    console.error('Failed to load media details from query params:', e)
    // Fall back to using basic query param data
    form.type = (query.type as string) || 'movie'
    form.title = (query.title as string) || ''
    form.year = query.year ? parseInt(query.year as string) : undefined
    form.poster_url = (query.poster_url as string) || ''
    form.external_id = (query.external_id as string) || ''
    form.external_source = (query.external_source as string) || ''
    form.is_custom = false

    updateAvailableFormats()
    step.value = 'details'
  } finally {
    loading.value = false
  }
}

function updateAvailableFormats() {
  const type = types.value.find(t => t.value === form.type)
  availableFormats.value = type?.formats || {}
}

const debouncedSearch = useDebounceFn(async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  loading.value = true
  try {
    if (searchType.value === 'movie' || searchType.value === 'series') {
      searchResults.value = await mediaService.searchMovies(searchQuery.value, searchType.value)
    } else if (searchType.value === 'album') {
      searchResults.value = await mediaService.searchAlbums(searchQuery.value)
    } else if (searchType.value === 'song') {
      searchResults.value = await mediaService.searchSongs(searchQuery.value)
    }
  } catch (e) {
    console.error('Search failed:', e)
  } finally {
    loading.value = false
  }
}, 400)

async function selectResult(result: MediaSearchResult) {
  selectedResult.value = result
  loading.value = true

  try {
    let details = result
    if (result.external_source === 'omdb') {
      details = await mediaService.getMovieDetails(result.external_id)
    } else if (result.external_source === 'musicbrainz' && result.type === 'album') {
      details = await mediaService.getAlbumDetails(result.external_id)
    }

    form.type = details.type
    form.title = details.title
    form.year = details.year
    form.description = details.description || ''
    form.poster_url = details.poster_url || ''
    form.external_id = details.external_id
    form.external_source = details.external_source
    form.is_custom = false
    form.metadata = details.metadata || {}

    updateAvailableFormats()
    step.value = 'details'
  } catch (e) {
    console.error('Failed to get details:', e)
  } finally {
    loading.value = false
  }
}

function startCustom() {
  isCustom.value = true
  selectedResult.value = null
  form.type = searchType.value === 'series' ? 'tv_show' : searchType.value
  form.title = searchQuery.value
  form.year = undefined
  form.description = ''
  form.poster_url = ''
  form.external_id = ''
  form.external_source = ''
  form.is_custom = true
  form.metadata = {}
  updateAvailableFormats()
  step.value = 'details'
}

function toggleFormat(format: string) {
  const index = form.formats.indexOf(format)
  if (index === -1) {
    form.formats.push(format)
  } else {
    form.formats.splice(index, 1)
  }
}

function toggleTag(tagId: string) {
  const index = form.tag_ids.indexOf(tagId)
  if (index === -1) {
    form.tag_ids.push(tagId)
  } else {
    form.tag_ids.splice(index, 1)
  }
}

function goBack() {
  if (step.value === 'details') {
    step.value = 'search'
    selectedResult.value = null
    isCustom.value = false
    showDetails.value = false
    currentBarcode.value = null
  } else if (step.value === 'barcode-results' || step.value === 'box-set') {
    step.value = 'search'
    currentBarcode.value = null
    barcodeMatches.value = []
    upcProduct.value = null
    isBoxSet.value = false
    franchiseName.value = null
  } else {
    router.back()
  }
}

// Barcode scanning functions
function openBarcodeScanner() {
  showBarcodeScanner.value = true
}

function closeBarcodeScanner() {
  showBarcodeScanner.value = false
}

async function handleBarcodeDetected(barcode: string, format: string) {
  showBarcodeScanner.value = false
  currentBarcode.value = barcode
  barcodeLoading.value = true
  searchType.value = 'movie' // Barcodes are typically for movies/TV

  try {
    // First, check our internal database
    const internalResult = await barcodeService.lookupInternal(barcode)

    if (internalResult.found && internalResult.high_confidence_match) {
      // High confidence match (3+ votes, single result or 2x second place)
      // Auto-fill and go to details
      const match = internalResult.high_confidence_match
      await selectFromBarcodeMatch(match)
      return
    }

    if (internalResult.found && internalResult.matches.length > 0) {
      // Multiple matches or low confidence - show options
      barcodeMatches.value = internalResult.matches
      step.value = 'barcode-results'
      // Also fetch UPC data in background for fallback
      fetchUpcData(barcode)
      return
    }

    // Not in our database - check UPCitemdb
    await fetchUpcData(barcode)

    if (upcProduct.value) {
      // Check if it's a box set
      const boxSetCheck = await barcodeService.checkBoxSet(upcProduct.value.title)
      if (boxSetCheck.is_box_set) {
        isBoxSet.value = true
        franchiseName.value = boxSetCheck.franchise_name
        step.value = 'box-set'
        return
      }

      // Search OMDB with the extracted title
      const searchTitle = barcodeService.extractSearchTitle(upcProduct.value.title)
      searchQuery.value = searchTitle
      await debouncedSearch()
      step.value = 'barcode-results'
    } else {
      // Nothing found - let user search manually
      step.value = 'barcode-results'
    }
  } catch (e) {
    console.error('Barcode lookup failed:', e)
    step.value = 'barcode-results'
  } finally {
    barcodeLoading.value = false
  }
}

async function fetchUpcData(barcode: string) {
  upcProduct.value = await barcodeService.lookupUpcItemDb(barcode)

  // Pre-select format if detected
  if (upcProduct.value) {
    const detectedFormat = barcodeService.detectFormatFromTitle(upcProduct.value.title)
    if (detectedFormat && !form.formats.includes(detectedFormat)) {
      form.formats = [detectedFormat]
    }
  }
}

async function selectFromBarcodeMatch(match: BarcodeMatch) {
  // Use the media item from the match
  pendingMediaItemId.value = match.media_item.id
  form.type = match.media_item.type
  form.title = match.media_item.title
  form.year = match.media_item.year
  form.poster_url = match.media_item.poster_url || ''
  form.external_id = match.media_item.external_id || ''
  form.external_source = match.media_item.external_source || ''
  form.is_custom = false

  // Fetch full details if we have an external ID
  if (match.media_item.external_id && match.media_item.external_source === 'omdb') {
    try {
      const details = await mediaService.getMovieDetails(match.media_item.external_id)
      form.description = details.description || ''
      form.metadata = details.metadata || {}
    } catch (e) {
      console.error('Failed to fetch details:', e)
    }
  }

  updateAvailableFormats()
  step.value = 'details'
}

async function handleBarcodeMatchIncorrect() {
  // User says the high-confidence match is incorrect
  // Show all matches plus search results from UPC data
  if (upcProduct.value) {
    const searchTitle = barcodeService.extractSearchTitle(upcProduct.value.title)
    searchQuery.value = searchTitle
    await debouncedSearch()
  }
  step.value = 'barcode-results'
}

function handleBoxSetAddSingle() {
  // Add as single box set item
  if (upcProduct.value) {
    form.type = 'movie'
    form.title = upcProduct.value.title
    form.is_custom = true
    updateAvailableFormats()
    step.value = 'details'
  }
}

function handleBoxSetAddIndividual() {
  // Search for individual titles
  if (franchiseName.value) {
    searchQuery.value = franchiseName.value
    debouncedSearch()
  }
  step.value = 'barcode-results'
}

async function saveMedia() {
  saving.value = true
  try {
    const item = await mediaStore.createItem({
      type: form.type,
      title: form.title,
      year: form.year,
      description: form.description || undefined,
      poster_url: form.poster_url || undefined,
      external_id: form.external_id || undefined,
      external_source: form.external_source || undefined,
      is_custom: form.is_custom,
      formats: form.formats.length ? form.formats : undefined,
      rating: form.rating || undefined,
      notes: form.notes || undefined,
      location_id: form.location_id || undefined,
      tag_ids: form.tag_ids,
      metadata: Object.keys(form.metadata).length ? form.metadata : undefined,
      // Digital fields
      is_digital: form.is_digital,
      digital_platform: form.is_digital ? form.digital_platform || undefined : undefined,
      digital_path: form.is_digital && selectedPlatformRequiresPath.value ? form.digital_path || undefined : undefined,
    } as Parameters<typeof mediaStore.createItem>[0])

    // Create barcode association if we scanned one
    if (currentBarcode.value && item.media_item_id) {
      try {
        await barcodeService.createAssociation(currentBarcode.value, item.media_item_id)
      } catch (e) {
        // Don't fail the whole save if barcode association fails
        console.error('Failed to create barcode association:', e)
      }
    }

    router.push({ name: 'media-detail', params: { id: item.id } })
  } catch (e) {
    console.error('Failed to save:', e)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-4 md:p-6">
    <!-- Back Button -->
    <button @click="goBack" class="btn-ghost mb-4">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Back
    </button>

    <!-- Step 1: Search -->
    <div v-if="step === 'search'" class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-white mb-2">Add to Collection</h1>
        <p class="text-dark-400">Search for media or add custom content</p>
      </div>

      <!-- Type Selection -->
      <div class="flex gap-2 flex-wrap">
        <button
          v-for="type in [
            { value: 'movie', label: 'Movie' },
            { value: 'series', label: 'TV Show' },
            { value: 'album', label: 'Album' },
            { value: 'song', label: 'Song' },
          ]"
          :key="type.value"
          @click="searchType = type.value as typeof searchType; searchResults = []"
          class="px-4 py-2 rounded-lg text-sm transition-colors"
          :class="searchType === type.value
            ? 'bg-primary-600 text-white'
            : 'bg-dark-700 text-dark-300 hover:bg-dark-600'"
        >
          {{ type.label }}
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="search"
          placeholder="Search for a title..."
          class="input pl-10"
        />
      </div>

      <!-- Scan Barcode Button (mobile only) -->
      <button
        v-if="barcodeScanAvailable"
        @click="openBarcodeScanner"
        class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-500 text-white rounded-lg font-medium flex items-center justify-center gap-3 hover:from-primary-500 hover:to-primary-400 transition-all shadow-lg"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
        </svg>
        Scan Barcode
      </button>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
      </div>

      <!-- Results -->
      <div v-else-if="searchResults.length > 0" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button
            v-for="result in searchResults"
            :key="result.external_id"
            @click="selectResult(result)"
            class="card-hover text-left"
          >
            <div class="aspect-[2/3] relative overflow-hidden rounded-lg">
              <img
                v-if="result.poster_url"
                :src="result.poster_url"
                :alt="result.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full bg-dark-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
              </div>
            </div>
            <div class="p-2">
              <h3 class="font-medium text-white text-sm line-clamp-2">{{ result.title }}</h3>
              <p v-if="result.year" class="text-xs text-dark-400 mt-1">{{ result.year }}</p>
            </div>
          </button>
        </div>

        <!-- Custom Option -->
        <div class="border-t border-dark-700 pt-4">
          <button @click="startCustom" class="btn-outline w-full">
            Can't find it? Add as custom media
          </button>
        </div>
      </div>

      <!-- No Results / Custom Option -->
      <div v-else-if="searchQuery && !loading" class="text-center py-8">
        <p class="text-dark-400 mb-4">No results found for "{{ searchQuery }}"</p>
        <button @click="startCustom" class="btn-primary">Add as Custom Media</button>
      </div>
    </div>

    <!-- Barcode Results Step -->
    <div v-else-if="step === 'barcode-results'" class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-white mb-2">Barcode Scanned</h1>
        <p class="text-dark-400">
          <span v-if="currentBarcode">Barcode: {{ currentBarcode }}</span>
        </p>
      </div>

      <!-- UPC Product Info -->
      <div v-if="upcProduct" class="card p-4 bg-dark-700/50">
        <p class="text-sm text-dark-400 mb-1">Product from barcode database:</p>
        <p class="text-white font-medium">{{ upcProduct.title }}</p>
      </div>

      <!-- Loading -->
      <div v-if="barcodeLoading" class="flex items-center justify-center py-8">
        <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
      </div>

      <!-- Database Matches -->
      <div v-if="barcodeMatches.length > 0" class="space-y-3">
        <p class="text-sm font-medium text-white">Previously matched titles:</p>
        <button
          v-for="match in barcodeMatches"
          :key="match.media_item.id"
          @click="selectFromBarcodeMatch(match)"
          class="w-full card p-3 flex items-center gap-3 text-left hover:bg-dark-700/50 transition-colors"
        >
          <img
            v-if="match.media_item.poster_url"
            :src="match.media_item.poster_url"
            :alt="match.media_item.title"
            class="w-12 h-18 object-cover rounded"
          />
          <div v-else class="w-12 h-18 bg-dark-600 rounded flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-white line-clamp-1">{{ match.media_item.title }}</h3>
            <p v-if="match.media_item.year" class="text-sm text-dark-400">{{ match.media_item.year }}</p>
          </div>
          <div class="text-xs text-dark-500">
            {{ match.vote_count }} {{ match.vote_count === 1 ? 'match' : 'matches' }}
          </div>
        </button>
      </div>

      <!-- Search Results -->
      <div v-if="searchResults.length > 0" class="space-y-3">
        <p class="text-sm font-medium text-white">Search results:</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button
            v-for="result in searchResults"
            :key="result.external_id"
            @click="selectResult(result)"
            class="card-hover text-left"
          >
            <div class="aspect-[2/3] relative overflow-hidden rounded-lg">
              <img
                v-if="result.poster_url"
                :src="result.poster_url"
                :alt="result.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full bg-dark-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
              </div>
            </div>
            <div class="p-2">
              <h3 class="font-medium text-white text-sm line-clamp-2">{{ result.title }}</h3>
              <p v-if="result.year" class="text-xs text-dark-400 mt-1">{{ result.year }}</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Manual Search Option -->
      <div class="space-y-3">
        <p class="text-sm text-dark-400">Not finding what you're looking for?</p>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="search"
            placeholder="Search manually..."
            class="input pl-10"
          />
        </div>
        <button @click="startCustom" class="btn-outline w-full">
          Add as custom media
        </button>
      </div>
    </div>

    <!-- Box Set Step -->
    <div v-else-if="step === 'box-set'" class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-white mb-2">Box Set Detected</h1>
        <p class="text-dark-400">This appears to be a collection or box set</p>
      </div>

      <!-- Product Info -->
      <div v-if="upcProduct" class="card p-4 bg-dark-700/50">
        <p class="text-white font-medium">{{ upcProduct.title }}</p>
        <p v-if="currentBarcode" class="text-sm text-dark-500 mt-1">Barcode: {{ currentBarcode }}</p>
      </div>

      <!-- Options -->
      <div class="space-y-4">
        <p class="text-sm text-white">How would you like to add this?</p>

        <button
          @click="handleBoxSetAddSingle"
          class="w-full card p-4 text-left hover:bg-dark-700/50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary-500/20 flex items-center justify-center">
              <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <h3 class="font-medium text-white">Add as single item</h3>
              <p class="text-sm text-dark-400">Add the entire box set as one collection item</p>
            </div>
          </div>
        </button>

        <button
          @click="handleBoxSetAddIndividual"
          class="w-full card p-4 text-left hover:bg-dark-700/50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center">
              <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </div>
            <div>
              <h3 class="font-medium text-white">Add individual titles</h3>
              <p class="text-sm text-dark-400">
                Search for "{{ franchiseName || 'titles' }}" and add each one separately
              </p>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Step 2: Details -->
    <div v-else-if="step === 'details'" class="space-y-6">
      <div class="flex items-start gap-4">
        <img
          v-if="form.poster_url"
          :src="form.poster_url"
          :alt="form.title"
          class="w-24 h-36 object-cover rounded-lg flex-shrink-0"
        />
        <div v-else class="w-24 h-36 bg-dark-700 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-8 h-8 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <span v-if="isCustom" class="badge bg-purple-500/20 text-purple-400 mb-2">Custom</span>
          <h1 class="text-xl font-bold text-white">{{ form.title || 'Untitled' }}</h1>
          <div class="flex flex-wrap items-center gap-2 text-sm text-dark-400 mt-1">
            <span v-if="form.year">{{ form.year }}</span>
            <span v-if="form.metadata?.runtime">• {{ form.metadata.runtime }}</span>
            <span v-if="form.metadata?.rated" class="px-1.5 py-0.5 bg-dark-600 rounded text-xs">{{ form.metadata.rated }}</span>
          </div>
          <div v-if="form.metadata?.genre" class="text-sm text-dark-300 mt-1 line-clamp-1">
            {{ form.metadata.genre }}
          </div>
          <!-- IMDb Rating -->
          <div v-if="form.metadata?.imdb_rating" class="flex items-center gap-1 mt-2">
            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span class="text-sm text-white font-medium">{{ form.metadata.imdb_rating }}</span>
            <span class="text-xs text-dark-500">/10</span>
            <span v-if="form.metadata?.imdb_votes" class="text-xs text-dark-500 ml-1">({{ form.metadata.imdb_votes }})</span>
          </div>
        </div>
      </div>

      <!-- Expandable Media Details Section -->
      <div v-if="hasDetails && !isCustom">
        <button
          @click="showDetails = !showDetails"
          class="flex items-center justify-between w-full p-3 bg-dark-700/50 rounded-lg text-left hover:bg-dark-700 transition-colors"
        >
          <span class="text-sm font-medium text-white">Media Details</span>
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
          <!-- Description -->
          <div v-if="form.description">
            <p class="text-sm text-dark-300 leading-relaxed">{{ form.description }}</p>
          </div>

          <!-- Director -->
          <div v-if="form.metadata?.director" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Director:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.director }}</span>
          </div>

          <!-- Writer -->
          <div v-if="form.metadata?.writer" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Writer:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.writer }}</span>
          </div>

          <!-- Actors/Cast -->
          <div v-if="form.metadata?.actors" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Cast:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.actors }}</span>
          </div>

          <!-- Awards -->
          <div v-if="form.metadata?.awards" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Awards:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.awards }}</span>
          </div>

          <!-- Box Office -->
          <div v-if="form.metadata?.box_office" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Box Office:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.box_office }}</span>
          </div>

          <!-- Language -->
          <div v-if="form.metadata?.language" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Language:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.language }}</span>
          </div>

          <!-- Country -->
          <div v-if="form.metadata?.country" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Country:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.country }}</span>
          </div>

          <!-- Total Seasons (for TV shows) -->
          <div v-if="form.metadata?.total_seasons" class="flex gap-2">
            <span class="text-sm text-dark-500 flex-shrink-0">Seasons:</span>
            <span class="text-sm text-dark-300">{{ form.metadata.total_seasons }}</span>
          </div>
        </div>
      </div>

      <!-- Custom Fields -->
      <div v-if="isCustom" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-white mb-2 block">Type</label>
          <select v-model="form.type" @change="updateAvailableFormats" class="input">
            <option v-for="type in types" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium text-white mb-2 block">Title</label>
          <input v-model="form.title" type="text" class="input" placeholder="Enter title" />
        </div>

        <div>
          <label class="text-sm font-medium text-white mb-2 block">Year</label>
          <input v-model="form.year" type="number" class="input" placeholder="e.g., 2024" />
        </div>

        <div>
          <label class="text-sm font-medium text-white mb-2 block">Description</label>
          <textarea v-model="form.description" rows="3" class="input" placeholder="Add a description..."></textarea>
        </div>
      </div>

      <!-- Formats -->
      <div>
        <p class="text-sm font-medium text-white mb-3">Formats Owned</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(label, value) in availableFormats"
            :key="value"
            @click="toggleFormat(value)"
            class="px-3 py-1.5 rounded-lg text-sm transition-colors"
            :class="form.formats.includes(value)
              ? 'bg-primary-600 text-white'
              : 'bg-dark-700 text-dark-300 hover:bg-dark-600'"
          >
            {{ label }}
          </button>
        </div>
      </div>

      <!-- Digital vs Physical Toggle -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-white">Digital Copy</p>
            <p class="text-xs text-dark-400 mt-0.5">Toggle on for digital media, off for physical media</p>
          </div>
          <button
            @click="form.is_digital = !form.is_digital"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
            :class="form.is_digital ? 'bg-primary-600' : 'bg-dark-600'"
          >
            <span
              class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
              :class="form.is_digital ? 'translate-x-6' : 'translate-x-1'"
            />
          </button>
        </div>

        <!-- Digital Platform Selection (shown when is_digital is true) -->
        <div v-if="form.is_digital && digitalPlatforms" class="space-y-4 animate-fade-in">
          <div>
            <label class="text-sm font-medium text-white mb-2 block">Digital Platform</label>
            <select v-model="form.digital_platform" class="input">
              <option value="">Select a platform...</option>
              <optgroup
                v-for="(category, categoryKey) in digitalPlatforms.grouped"
                :key="categoryKey"
                :label="category.label"
              >
                <option
                  v-for="platform in category.platforms"
                  :key="platform.id"
                  :value="platform.id"
                >
                  {{ platform.name }}
                </option>
              </optgroup>
              <!-- User-defined locations as additional options -->
              <optgroup v-if="locationStore.locations.length > 0" label="Your Custom Locations">
                <option
                  v-for="loc in locationStore.locations"
                  :key="`custom-${loc.id}`"
                  :value="`custom:${loc.id}`"
                >
                  {{ loc.name }}
                </option>
              </optgroup>
            </select>
          </div>

          <!-- Network Path Input (shown for NAS/local storage) -->
          <div v-if="selectedPlatformRequiresPath" class="animate-fade-in">
            <label class="text-sm font-medium text-white mb-2 block">Network/File Path</label>
            <input
              v-model="form.digital_path"
              type="text"
              class="input font-mono text-sm"
              placeholder="e.g., \\NAS\Movies\Title (2024) or /media/movies/title"
            />
            <p class="text-xs text-dark-400 mt-1">
              Enter the path to the folder or file on your storage
            </p>
          </div>
        </div>
      </div>

      <!-- Location -->
      <div>
        <label class="text-sm font-medium text-white mb-2 block">Location</label>
        <template v-if="locationStore.locations.length > 0">
          <select v-model="form.location_id" class="input">
            <option value="">No location</option>
            <option v-for="loc in locationStore.locations" :key="loc.id" :value="loc.id">
              {{ loc.name }}
            </option>
          </select>
        </template>
        <div v-else class="p-3 bg-dark-700/50 border border-dark-600 rounded-lg">
          <p class="text-sm text-dark-400">
            No locations defined yet.
            <router-link :to="{ name: 'locations' }" class="text-primary-400 hover:text-primary-300">
              Add locations
            </router-link>
            to track where your media is stored.
          </p>
        </div>
      </div>

      <!-- Tags -->
      <div>
        <p class="text-sm font-medium text-white mb-3">Tags</p>
        <template v-if="tagStore.tags.length > 0">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tag in tagStore.tags"
              :key="tag.id"
              @click="toggleTag(tag.id)"
              class="px-3 py-1.5 rounded-lg text-sm transition-colors"
              :class="form.tag_ids.includes(tag.id)
                ? 'text-white'
                : 'bg-dark-700 text-dark-300 hover:bg-dark-600'"
              :style="form.tag_ids.includes(tag.id) ? { backgroundColor: tag.color || '#3b82f6' } : {}"
            >
              {{ tag.name }}
            </button>
          </div>
        </template>
        <div v-else class="p-3 bg-dark-700/50 border border-dark-600 rounded-lg">
          <p class="text-sm text-dark-400">
            No tags defined yet.
            <router-link :to="{ name: 'tags' }" class="text-primary-400 hover:text-primary-300">
              Add tags
            </router-link>
            to organize your media collection.
          </p>
        </div>
      </div>

      <!-- Rating -->
      <div>
        <p class="text-sm font-medium text-white mb-3">Rating</p>
        <div class="flex gap-1">
          <button
            v-for="i in 10"
            :key="i"
            @click="form.rating = i"
            class="w-8 h-8 transition-colors"
            :class="i <= form.rating ? 'text-yellow-400' : 'text-dark-600 hover:text-dark-500'"
          >
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </button>
          <button v-if="form.rating > 0" @click="form.rating = 0" class="ml-2 text-sm text-dark-400 hover:text-white">
            Clear
          </button>
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="text-sm font-medium text-white mb-2 block">Notes</label>
        <textarea v-model="form.notes" rows="3" class="input" placeholder="Add personal notes..."></textarea>
      </div>

      <!-- Barcode Info (if scanned) -->
      <div v-if="currentBarcode" class="p-3 bg-dark-700/50 rounded-lg">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
            <span class="text-sm text-dark-300">Barcode: {{ currentBarcode }}</span>
          </div>
          <button
            @click="handleBarcodeMatchIncorrect"
            class="text-xs text-dark-400 hover:text-white"
          >
            Wrong match?
          </button>
        </div>
      </div>

      <!-- Save Button -->
      <button
        @click="saveMedia"
        :disabled="!form.title || saving"
        class="btn-primary w-full"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Add to Collection
      </button>
    </div>

    <!-- Barcode Scanner Modal -->
    <Teleport to="body">
      <BarcodeScanner
        v-if="showBarcodeScanner"
        @detected="handleBarcodeDetected"
        @close="closeBarcodeScanner"
        @error="(e) => { console.error('Scanner error:', e); closeBarcodeScanner() }"
      />
    </Teleport>
  </div>
</template>
