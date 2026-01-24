import api from './api'
import type { Tag } from '@/types'

export const tagService = {
  async getTags(): Promise<Tag[]> {
    const response = await api.get<{ data: Tag[] }>('/tags')
    return response.data.data
  },

  async createTag(data: { name: string; color?: string }): Promise<Tag> {
    const response = await api.post<{ data: Tag }>('/tags', data)
    return response.data.data
  },

  async updateTag(id: string, data: { name?: string; color?: string }): Promise<Tag> {
    const response = await api.put<{ data: Tag }>(`/tags/${id}`, data)
    return response.data.data
  },

  async deleteTag(id: string): Promise<void> {
    await api.delete(`/tags/${id}`)
  },
}
