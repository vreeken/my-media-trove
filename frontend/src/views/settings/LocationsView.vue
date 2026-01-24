<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useLocationStore } from '@/stores/locations'
import type { Location } from '@/types'

const router = useRouter()
const locationStore = useLocationStore()

const showModal = ref(false)
const editingLocation = ref<Location | null>(null)
const form = reactive({
  name: '',
  description: '',
})
const deleteConfirmId = ref<string | null>(null)

onMounted(async () => {
  await locationStore.fetchLocations()
})

function openCreateModal() {
  editingLocation.value = null
  form.name = ''
  form.description = ''
  showModal.value = true
}

function openEditModal(location: Location) {
  editingLocation.value = location
  form.name = location.name
  form.description = location.description || ''
  showModal.value = true
}

async function saveLocation() {
  if (editingLocation.value) {
    await locationStore.updateLocation(editingLocation.value.id, {
      name: form.name,
      description: form.description || undefined,
    })
  } else {
    await locationStore.createLocation({
      name: form.name,
      description: form.description || undefined,
    })
  }
  showModal.value = false
}

async function deleteLocation(id: string) {
  await locationStore.deleteLocation(id)
  deleteConfirmId.value = null
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <!-- Back Button -->
    <button @click="router.back()" class="btn-ghost">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Back
    </button>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Locations</h1>
        <p class="text-dark-400">Manage where your physical media is stored</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add
      </button>
    </div>

    <!-- Loading -->
    <div v-if="locationStore.loading && locationStore.locations.length === 0" class="flex items-center justify-center py-12">
      <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="locationStore.locations.length === 0" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">No locations yet</h3>
      <p class="text-dark-400 mb-4">Add locations like "Living Room" or "Bedroom Shelf"</p>
      <button @click="openCreateModal" class="btn-primary">Add Location</button>
    </div>

    <!-- Locations List -->
    <div v-else class="space-y-3">
      <div
        v-for="location in locationStore.locations"
        :key="location.id"
        class="card p-4 flex items-center justify-between"
      >
        <div>
          <h3 class="font-medium text-white">{{ location.name }}</h3>
          <p v-if="location.description" class="text-sm text-dark-400">{{ location.description }}</p>
          <p class="text-xs text-dark-500 mt-1">{{ location.media_items_count ?? 0 }} items</p>
        </div>
        <div class="flex gap-2">
          <button @click="openEditModal(location)" class="btn-ghost btn-sm">
            Edit
          </button>
          <button @click="deleteConfirmId = location.id" class="btn-ghost btn-sm text-red-400">
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showModal = false"
      >
        <div class="card p-6 w-full max-w-md animate-fade-in">
          <h3 class="text-lg font-semibold text-white mb-4">
            {{ editingLocation ? 'Edit Location' : 'Add Location' }}
          </h3>

          <form @submit.prevent="saveLocation" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">Name</label>
              <input v-model="form.name" type="text" class="input" placeholder="e.g., Living Room Shelf" required />
            </div>

            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">Description (optional)</label>
              <textarea v-model="form.description" rows="2" class="input" placeholder="Additional details..."></textarea>
            </div>

            <div class="flex gap-3">
              <button type="submit" class="btn-primary flex-1">Save</button>
              <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation -->
    <Teleport to="body">
      <div
        v-if="deleteConfirmId"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="deleteConfirmId = null"
      >
        <div class="card p-6 w-full max-w-sm animate-fade-in">
          <h3 class="text-lg font-semibold text-white mb-2">Delete Location?</h3>
          <p class="text-dark-400 mb-6">Media items in this location won't be deleted, but will no longer have a location set.</p>
          <div class="flex gap-3">
            <button @click="deleteLocation(deleteConfirmId!)" class="btn-danger flex-1">Delete</button>
            <button @click="deleteConfirmId = null" class="btn-secondary flex-1">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
