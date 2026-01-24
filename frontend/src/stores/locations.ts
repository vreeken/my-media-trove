import { defineStore } from 'pinia'
import { ref } from 'vue'
import { locationService } from '@/services/locations'
import type { Location } from '@/types'

export const useLocationStore = defineStore('locations', () => {
  const locations = ref<Location[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchLocations() {
    try {
      loading.value = true
      error.value = null
      locations.value = await locationService.getLocations()
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load locations'
    } finally {
      loading.value = false
    }
  }

  async function createLocation(data: { name: string; description?: string }) {
    try {
      loading.value = true
      error.value = null
      const location = await locationService.createLocation(data)
      locations.value = [...locations.value, location]
      return location
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to create location'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateLocation(id: string, data: { name?: string; description?: string }) {
    try {
      loading.value = true
      error.value = null
      const updated = await locationService.updateLocation(id, data)
      const index = locations.value.findIndex(l => l.id === id)
      if (index !== -1) {
        locations.value[index] = updated
      }
      return updated
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to update location'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteLocation(id: string) {
    try {
      loading.value = true
      error.value = null
      await locationService.deleteLocation(id)
      locations.value = locations.value.filter(l => l.id !== id)
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to delete location'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    locations,
    loading,
    error,
    fetchLocations,
    createLocation,
    updateLocation,
    deleteLocation,
  }
})
