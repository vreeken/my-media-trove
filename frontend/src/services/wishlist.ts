import api from './api'
import type { WishlistItem, PaginatedResponse } from '@/types'

export const wishlistService = {
  async getWishlist(params?: { type?: string; priority?: number; search?: string }): Promise<PaginatedResponse<WishlistItem>> {
    const response = await api.get<PaginatedResponse<WishlistItem>>('/wishlist', { params })
    return response.data
  },

  async addToWishlist(data: Partial<WishlistItem>): Promise<WishlistItem> {
    const response = await api.post<{ data: WishlistItem }>('/wishlist', data)
    return response.data.data
  },

  async updateWishlistItem(id: string, data: { notes?: string; priority?: number }): Promise<WishlistItem> {
    const response = await api.put<{ data: WishlistItem }>(`/wishlist/${id}`, data)
    return response.data.data
  },

  async removeFromWishlist(id: string): Promise<void> {
    await api.delete(`/wishlist/${id}`)
  },

  async moveToCollection(id: string, data: {
    formats?: string[]
    location_id?: string
    rating?: number
    notes?: string
    tag_ids?: string[]
  }): Promise<{ user_media_item_id: string }> {
    const response = await api.post<{ data: { user_media_item_id: string } }>(`/wishlist/${id}/to-collection`, data)
    return response.data.data
  },
}
