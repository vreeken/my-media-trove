import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { mediaService } from '@/services/media'
import type { MediaItem, MediaType, MediaItemFilters, PaginationMeta } from '@/types'

export const useMediaStore = defineStore('media', () => {
  const items = ref<MediaItem[]>([])
  const currentItem = ref<MediaItem | null>(null)
  const types = ref<MediaType[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<MediaItemFilters>({})

  const stats = ref<{
    total: number
    by_type: Record<string, { count: number; label: string }>
    custom_count: number
  } | null>(null)

  const hasMore = computed(() => {
    if (!pagination.value) return false
    return pagination.value.current_page < pagination.value.last_page
  })

  async function fetchCollection(newFilters?: MediaItemFilters) {
    try {
      loading.value = true
      error.value = null

      if (newFilters) {
        filters.value = { ...newFilters }
      }

      const response = await mediaService.getCollection(filters.value)
      items.value = response.data
      pagination.value = response.meta
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load collection'
    } finally {
      loading.value = false
    }
  }

  async function loadMore() {
    if (!hasMore.value || loading.value) return

    try {
      loading.value = true
      const nextPage = (pagination.value?.current_page || 0) + 1
      const response = await mediaService.getCollection({ ...filters.value, page: nextPage })
      items.value = [...items.value, ...response.data]
      pagination.value = response.meta
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load more items'
    } finally {
      loading.value = false
    }
  }

  async function fetchItem(id: string) {
    try {
      loading.value = true
      error.value = null
      currentItem.value = await mediaService.getMediaItem(id)
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load item'
    } finally {
      loading.value = false
    }
  }

  async function createItem(data: Partial<MediaItem>) {
    try {
      loading.value = true
      error.value = null
      const item = await mediaService.createMediaItem(data)
      items.value = [item, ...items.value]
      return item
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to create item'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateItem(id: string, data: Partial<MediaItem>) {
    try {
      loading.value = true
      error.value = null
      const updated = await mediaService.updateMediaItem(id, data)

      const index = items.value.findIndex(i => i.id === id)
      if (index !== -1) {
        items.value[index] = updated
      }

      if (currentItem.value?.id === id) {
        currentItem.value = updated
      }

      return updated
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to update item'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteItem(id: string) {
    try {
      loading.value = true
      error.value = null
      await mediaService.deleteMediaItem(id)
      items.value = items.value.filter(i => i.id !== id)

      if (currentItem.value?.id === id) {
        currentItem.value = null
      }
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to delete item'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchTypes() {
    if (types.value.length > 0) return

    try {
      types.value = await mediaService.getTypes()
    } catch {
      console.error('Failed to load media types')
    }
  }

  async function fetchStats() {
    try {
      stats.value = await mediaService.getStats()
    } catch {
      console.error('Failed to load stats')
    }
  }

  function clearFilters() {
    filters.value = {}
  }

  return {
    items,
    currentItem,
    types,
    pagination,
    loading,
    error,
    filters,
    stats,
    hasMore,
    fetchCollection,
    loadMore,
    fetchItem,
    createItem,
    updateItem,
    deleteItem,
    fetchTypes,
    fetchStats,
    clearFilters,
  }
})
