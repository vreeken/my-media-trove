<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMediaStore } from '@/stores/media'
import { useTagStore } from '@/stores/tags'
import { useLocationStore } from '@/stores/locations'
import { mediaService } from '@/services/media'
import StarRating from '@/components/ui/StarRating.vue'
import { canOpenPlatformLink } from '@/utils/digitalPlatforms'
import type { DigitalPlatformsResponse, DigitalPlatform } from '@/types'

const route = useRoute()
const router = useRouter()
const mediaStore = useMediaStore()
const tagStore = useTagStore()
const locationStore = useLocationStore()

const isEditing = ref(false)
const showDeleteConfirm = ref(false)
const digitalPlatforms = ref<DigitalPlatformsResponse | null>(null)

const editForm = ref({
  rating: 0,
  notes: '',
  formats: [] as string[],
  location_id: '',
  tag_ids: [] as string[],
  is_digital: false,
  digital_platform: '',
  digital_path: '',
})

const item = computed(() => mediaStore.currentItem)

// Check if the digital platform has a linkable URL
const hasPlatformLink = computed(() => {
  if (!item.value) return false
  return canOpenPlatformLink(item.value)
})

// Check if selected platform requires a path input
const selectedPlatformRequiresPath = computed(() => {
  if (!editForm.value.is_digital || !editForm.value.digital_platform || !digitalPlatforms.value) return false
  const platform = digitalPlatforms.value.flat.find((p: DigitalPlatform) => p.id === editForm.value.digital_platform)
  return platform?.requires_path ?? false
})

// Clear digital fields when toggling off
watch(() => editForm.value.is_digital, (isDigital) => {
  if (!isDigital) {
    editForm.value.digital_platform = ''
    editForm.value.digital_path = ''
  }
})

// Clear path when platform doesn't require it
watch(() => editForm.value.digital_platform, () => {
  if (!selectedPlatformRequiresPath.value) {
    editForm.value.digital_path = ''
  }
})

onMounted(async () => {
  const id = route.params.id as string
  await Promise.all([
    mediaStore.fetchItem(id),
    mediaStore.fetchTypes(),
    tagStore.fetchTags(),
    locationStore.fetchLocations(),
    loadDigitalPlatforms(),
  ])
})

async function loadDigitalPlatforms() {
  try {
    digitalPlatforms.value = await mediaService.getDigitalPlatforms()
  } catch (e) {
    console.error('Failed to load digital platforms:', e)
  }
}

// Initialization after data loaded
watch(item, (newItem) => {
  if (newItem) {
    editForm.value = {
      rating: newItem.rating || 0,
      notes: newItem.notes || '',
      formats: newItem.formats || [],
      location_id: newItem.location?.id || '',
      tag_ids: newItem.tags.map(t => t.id),
      is_digital: newItem.is_digital || false,
      digital_platform: newItem.digital_platform || '',
      digital_path: newItem.digital_path || '',
    }
  }
}, { immediate: true })

function startEditing() {
  if (item.value) {
    editForm.value = {
      rating: item.value.rating || 0,
      notes: item.value.notes || '',
      formats: item.value.formats || [],
      location_id: item.value.location?.id || '',
      tag_ids: item.value.tags.map(t => t.id),
      is_digital: item.value.is_digital || false,
      digital_platform: item.value.digital_platform || '',
      digital_path: item.value.digital_path || '',
    }
  }
  isEditing.value = true
}

async function saveChanges() {
  if (!item.value) return

  await mediaStore.updateItem(item.value.id, {
    rating: editForm.value.rating || undefined,
    notes: editForm.value.notes || undefined,
    formats: editForm.value.formats,
    location_id: editForm.value.location_id || undefined,
    tag_ids: editForm.value.tag_ids,
    is_digital: editForm.value.is_digital,
    digital_platform: editForm.value.is_digital ? editForm.value.digital_platform || undefined : undefined,
    digital_path: editForm.value.is_digital ? editForm.value.digital_path || undefined : undefined,
  })

  isEditing.value = false
}

async function deleteItem() {
  if (!item.value) return

  await mediaStore.deleteItem(item.value.id)
  router.push({ name: 'collection' })
}

function toggleFormat(format: string) {
  const index = editForm.value.formats.indexOf(format)
  if (index === -1) {
    editForm.value.formats.push(format)
  } else {
    editForm.value.formats.splice(index, 1)
  }
}

function toggleTag(tagId: string) {
  const index = editForm.value.tag_ids.indexOf(tagId)
  if (index === -1) {
    editForm.value.tag_ids.push(tagId)
  } else {
    editForm.value.tag_ids.splice(index, 1)
  }
}
</script>

<template>
  <div class="p-4 md:p-6">
    <!-- Back Button -->
    <button @click="router.back()" class="btn-ghost mb-4">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Back
    </button>

    <!-- Loading State -->
    <div v-if="mediaStore.loading && !item" class="card p-6">
      <div class="animate-pulse space-y-4">
        <div class="skeleton h-64 w-48"></div>
        <div class="skeleton h-8 w-3/4"></div>
        <div class="skeleton h-4 w-1/2"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="item" class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Poster -->
        <div class="flex-shrink-0">
          <img
            v-if="item.poster_url"
            :src="item.poster_url"
            :alt="item.title"
            class="w-48 h-72 object-cover rounded-xl mx-auto md:mx-0"
          />
          <div
            v-else
            class="w-48 h-72 bg-dark-700 rounded-xl flex items-center justify-center mx-auto md:mx-0"
          >
            <svg class="w-16 h-16 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
          </div>
        </div>

        <!-- Info -->
        <div class="flex-1 space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="badge-primary">{{ item.type_label }}</span>
              <span v-if="item.is_custom" class="badge bg-purple-500/20 text-purple-400 ml-2">Custom</span>
            </div>
            <div class="flex gap-2">
              <button
                v-if="!isEditing"
                @click="startEditing"
                class="btn-secondary btn-sm"
              >
                Edit
              </button>
              <button
                v-if="!isEditing"
                @click="showDeleteConfirm = true"
                class="btn-danger btn-sm"
              >
                Delete
              </button>
            </div>
          </div>

          <h1 class="text-2xl md:text-3xl font-bold text-white">{{ item.title }}</h1>

          <div class="flex flex-wrap items-center gap-3 text-dark-300">
            <span v-if="item.year">{{ item.year }}</span>
            <span v-if="item.metadata?.runtime">{{ item.metadata.runtime }}</span>
            <span v-if="item.metadata?.genre">{{ item.metadata.genre }}</span>
          </div>

          <!-- Rating -->
          <div v-if="!isEditing">
            <p class="text-sm text-dark-400 mb-1">Your Rating</p>
            <StarRating :rating="item.rating || 0" :readonly="true" />
          </div>
          <div v-else>
            <p class="text-sm text-dark-400 mb-1">Your Rating</p>
            <StarRating v-model:rating="editForm.rating" />
          </div>

          <!-- Description -->
          <p v-if="item.description" class="text-dark-300 leading-relaxed">
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Edit Form -->
      <div v-if="isEditing" class="card p-6 space-y-6">
        <!-- Formats -->
        <div>
          <p class="text-sm font-medium text-white mb-3">Formats Owned</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(label, value) in item.available_formats"
              :key="value"
              @click="toggleFormat(value)"
              class="px-3 py-1.5 rounded-lg text-sm transition-colors"
              :class="editForm.formats.includes(value)
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
              <p class="text-xs text-dark-400 mt-0.5">Toggle on for digital media</p>
            </div>
            <button
              @click="editForm.is_digital = !editForm.is_digital"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              :class="editForm.is_digital ? 'bg-primary-600' : 'bg-dark-600'"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="editForm.is_digital ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
          </div>

          <!-- Digital Platform Selection -->
          <div v-if="editForm.is_digital && digitalPlatforms" class="space-y-4 animate-fade-in">
            <div>
              <label class="text-sm font-medium text-white mb-2 block">Digital Platform</label>
              <select v-model="editForm.digital_platform" class="input">
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
              </select>
            </div>

            <!-- Network Path Input -->
            <div v-if="selectedPlatformRequiresPath" class="animate-fade-in">
              <label class="text-sm font-medium text-white mb-2 block">Network/File Path</label>
              <input
                v-model="editForm.digital_path"
                type="text"
                class="input font-mono text-sm"
                placeholder="e.g., \\NAS\Movies\Title (2024)"
              />
            </div>
          </div>
        </div>

        <!-- Location -->
        <div>
          <label class="text-sm font-medium text-white mb-2 block">Location</label>
          <select v-model="editForm.location_id" class="input">
            <option value="">No location</option>
            <option v-for="loc in locationStore.locations" :key="loc.id" :value="loc.id">
              {{ loc.name }}
            </option>
          </select>
        </div>

        <!-- Tags -->
        <div>
          <p class="text-sm font-medium text-white mb-3">Tags</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tag in tagStore.tags"
              :key="tag.id"
              @click="toggleTag(tag.id)"
              class="px-3 py-1.5 rounded-lg text-sm transition-colors"
              :class="editForm.tag_ids.includes(tag.id)
                ? 'text-white'
                : 'bg-dark-700 text-dark-300 hover:bg-dark-600'"
              :style="editForm.tag_ids.includes(tag.id) ? { backgroundColor: tag.color || '#3b82f6' } : {}"
            >
              {{ tag.name }}
            </button>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="text-sm font-medium text-white mb-2 block">Notes</label>
          <textarea
            v-model="editForm.notes"
            rows="4"
            class="input"
            placeholder="Add your personal notes..."
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button @click="saveChanges" class="btn-primary">Save Changes</button>
          <button @click="isEditing = false" class="btn-secondary">Cancel</button>
        </div>
      </div>

      <!-- Display Info (when not editing) -->
      <div v-else class="space-y-6">
        <!-- Formats -->
        <div v-if="item.formats?.length" class="card p-4">
          <p class="text-sm font-medium text-white mb-3">Formats Owned</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="format in item.formats"
              :key="format"
              class="badge-primary"
            >
              {{ item.available_formats[format] || format }}
            </span>
          </div>
        </div>

        <!-- Digital Platform -->
        <div v-if="item.is_digital && item.digital_platform" class="card p-4">
          <p class="text-sm font-medium text-white mb-2">Digital Platform</p>
          <div class="flex items-center gap-3">
            <!-- Digital badge -->
            <span class="badge bg-cyan-500/20 text-cyan-400">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
              </svg>
              Digital
            </span>

            <!-- Platform name with optional link -->
            <template v-if="hasPlatformLink">
              <a
                :href="item.digital_platform_search_url || item.digital_platform_url || '#'"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-primary-400 hover:text-primary-300 transition-colors"
              >
                {{ item.digital_platform_name || item.digital_platform }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </template>
            <template v-else>
              <span class="text-dark-300">{{ item.digital_platform_name || item.digital_platform }}</span>
            </template>
          </div>

          <!-- Digital path (for NAS/local storage) -->
          <div v-if="item.digital_path" class="mt-3">
            <p class="text-xs text-dark-500 mb-1">File Path</p>
            <code class="text-sm text-dark-300 bg-dark-700 px-2 py-1 rounded block break-all">
              {{ item.digital_path }}
            </code>
          </div>
        </div>

        <!-- Location -->
        <div v-if="item.location" class="card p-4">
          <p class="text-sm font-medium text-white mb-2">Location</p>
          <p class="text-dark-300">{{ item.location.name }}</p>
        </div>

        <!-- Tags -->
        <div v-if="item.tags?.length" class="card p-4">
          <p class="text-sm font-medium text-white mb-3">Tags</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in item.tags"
              :key="tag.id"
              class="px-3 py-1 rounded-full text-sm text-white"
              :style="{ backgroundColor: tag.color || '#3b82f6' }"
            >
              {{ tag.name }}
            </span>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="item.notes" class="card p-4">
          <p class="text-sm font-medium text-white mb-2">Notes</p>
          <p class="text-dark-300 whitespace-pre-wrap">{{ item.notes }}</p>
        </div>

        <!-- Metadata -->
        <div v-if="item.metadata && Object.keys(item.metadata).length" class="card p-4">
          <p class="text-sm font-medium text-white mb-3">Details</p>
          <dl class="grid grid-cols-2 gap-3 text-sm">
            <template v-for="(value, key) in item.metadata" :key="key">
              <div v-if="value && typeof value !== 'object'">
                <dt class="text-dark-400 capitalize">{{ String(key).replace(/_/g, ' ') }}</dt>
                <dd class="text-white">{{ value }}</dd>
              </div>
            </template>
          </dl>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div
        v-if="showDeleteConfirm"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showDeleteConfirm = false"
      >
        <div class="card p-6 max-w-sm w-full animate-fade-in">
          <h3 class="text-lg font-semibold text-white mb-2">Delete Media?</h3>
          <p class="text-dark-400 mb-6">
            Are you sure you want to delete "{{ item?.title }}"? This action cannot be undone.
          </p>
          <div class="flex gap-3">
            <button @click="deleteItem" class="btn-danger flex-1">Delete</button>
            <button @click="showDeleteConfirm = false" class="btn-secondary flex-1">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
