import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { wishlistService } from '@/services/wishlist'
import type { WishlistItem, PaginationMeta } from '@/types'

export const useWishlistStore = defineStore('wishlist', () => {
  const items = ref<WishlistItem[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const hasMore = computed(() => {
    if (!pagination.value) return false
    return pagination.value.current_page < pagination.value.last_page
  })

  // Check if an item is in the wishlist by external_id
  function isInWishlist(externalId: string): boolean {
    return items.value.some(item => item.external_id === externalId)
  }

  // Get wishlist item by external_id
  function getByExternalId(externalId: string): WishlistItem | undefined {
    return items.value.find(item => item.external_id === externalId)
  }

  async function fetchWishlist(params?: { type?: string; priority?: number; search?: string }) {
    try {
      loading.value = true
      error.value = null
      const response = await wishlistService.getWishlist(params)
      items.value = response.data
      pagination.value = response.meta
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load wishlist'
    } finally {
      loading.value = false
    }
  }

  async function addItem(data: Partial<WishlistItem>) {
    try {
      loading.value = true
      error.value = null
      const item = await wishlistService.addToWishlist(data)
      items.value = [item, ...items.value]
      return item
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to add to wishlist'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateItem(id: string, data: { notes?: string; priority?: number }) {
    try {
      loading.value = true
      error.value = null
      const updated = await wishlistService.updateWishlistItem(id, data)
      const index = items.value.findIndex(i => i.id === id)
      if (index !== -1) {
        items.value[index] = updated
      }
      return updated
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to update wishlist item'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function removeItem(id: string) {
    try {
      loading.value = true
      error.value = null
      await wishlistService.removeFromWishlist(id)
      items.value = items.value.filter(i => i.id !== id)
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to remove from wishlist'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function moveToCollection(id: string, data: {
    formats?: string[]
    location_id?: string
    rating?: number
    notes?: string
  }) {
    try {
      loading.value = true
      error.value = null
      const result = await wishlistService.moveToCollection(id, data)
      items.value = items.value.filter(i => i.id !== id)
      return result
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to move to collection'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    pagination,
    loading,
    error,
    hasMore,
    isInWishlist,
    getByExternalId,
    fetchWishlist,
    addItem,
    updateItem,
    removeItem,
    moveToCollection,
  }
})
