import api from './api'
import type { Location } from '@/types'

export const locationService = {
  async getLocations(): Promise<Location[]> {
    const response = await api.get<{ data: Location[] }>('/locations')
    return response.data.data
  },

  async createLocation(data: { name: string; description?: string }): Promise<Location> {
    const response = await api.post<{ data: Location }>('/locations', data)
    return response.data.data
  },

  async updateLocation(id: string, data: { name?: string; description?: string }): Promise<Location> {
    const response = await api.put<{ data: Location }>(`/locations/${id}`, data)
    return response.data.data
  },

  async deleteLocation(id: string): Promise<void> {
    await api.delete(`/locations/${id}`)
  },
}
