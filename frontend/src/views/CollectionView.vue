<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { useMediaStore } from '@/stores/media'
import { useTagStore } from '@/stores/tags'
import { useLocationStore } from '@/stores/locations'
import MediaCard from '@/components/media/MediaCard.vue'

const router = useRouter()
const mediaStore = useMediaStore()
const tagStore = useTagStore()
const locationStore = useLocationStore()

const searchQuery = ref('')
const selectedType = ref('')
const selectedTag = ref('')
const selectedLocation = ref('')
const sortBy = ref('created_at')
const sortDir = ref<'asc' | 'desc'>('desc')

onMounted(async () => {
  await Promise.all([
    mediaStore.fetchCollection(),
    mediaStore.fetchTypes(),
    tagStore.fetchTags(),
    locationStore.fetchLocations(),
  ])
})

const debouncedSearch = useDebounceFn(() => {
  applyFilters()
}, 300)

watch(searchQuery, () => {
  debouncedSearch()
})

function applyFilters() {
  mediaStore.fetchCollection({
    search: searchQuery.value || undefined,
    type: selectedType.value || undefined,
    tag_id: selectedTag.value || undefined,
    location_id: selectedLocation.value || undefined,
    sort_by: sortBy.value,
    sort_dir: sortDir.value,
  })
}

function clearFilters() {
  searchQuery.value = ''
  selectedType.value = ''
  selectedTag.value = ''
  selectedLocation.value = ''
  sortBy.value = 'created_at'
  sortDir.value = 'desc'
  mediaStore.fetchCollection()
}

function viewMedia(id: string) {
  router.push({ name: 'media-detail', params: { id } })
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Search and Filters -->
    <div class="space-y-3">
      <!-- Search -->
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search your collection..."
          class="input pl-10"
        />
      </div>

      <!-- Filter Row -->
      <div class="flex flex-wrap gap-2">
        <select
          v-model="selectedType"
          @change="applyFilters"
          class="input w-auto"
        >
          <option value="">All Types</option>
          <option v-for="type in mediaStore.types" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>

        <select
          v-model="selectedTag"
          @change="applyFilters"
          class="input w-auto"
        >
          <option value="">All Tags</option>
          <option v-for="tag in tagStore.tags" :key="tag.id" :value="tag.id">
            {{ tag.name }}
          </option>
        </select>

        <select
          v-model="selectedLocation"
          @change="applyFilters"
          class="input w-auto"
        >
          <option value="">All Locations</option>
          <option v-for="loc in locationStore.locations" :key="loc.id" :value="loc.id">
            {{ loc.name }}
          </option>
        </select>

        <select
          v-model="sortBy"
          @change="applyFilters"
          class="input w-auto"
        >
          <option value="created_at">Date Added</option>
          <option value="title">Title</option>
          <option value="year">Year</option>
          <option value="rating">Rating</option>
        </select>

        <button
          @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'; applyFilters()"
          class="btn-secondary"
        >
          <svg class="w-4 h-4" :class="{ 'rotate-180': sortDir === 'asc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </button>

        <button
          v-if="searchQuery || selectedType || selectedTag || selectedLocation"
          @click="clearFilters"
          class="btn-ghost text-sm"
        >
          Clear filters
        </button>
      </div>
    </div>

    <!-- Results Count -->
    <div class="text-sm text-dark-400">
      {{ mediaStore.pagination?.total ?? 0 }} items
    </div>

    <!-- Loading State -->
    <div v-if="mediaStore.loading && mediaStore.items.length === 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="i in 8" :key="i" class="card aspect-[2/3]">
        <div class="skeleton w-full h-full"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="mediaStore.items.length === 0" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">No media found</h3>
      <p class="text-dark-400 mb-4">Start building your collection by adding some media</p>
      <router-link to="/add" class="btn-primary">Add Media</router-link>
    </div>

    <!-- Media Grid -->
    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <MediaCard
        v-for="item in mediaStore.items"
        :key="item.id"
        :item="item"
        @click="viewMedia(item.id)"
      />
    </div>

    <!-- Load More -->
    <div v-if="mediaStore.hasMore" class="text-center pt-4">
      <button
        @click="mediaStore.loadMore()"
        :disabled="mediaStore.loading"
        class="btn-secondary"
      >
        <svg v-if="mediaStore.loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Load More
      </button>
    </div>
  </div>
</template>
