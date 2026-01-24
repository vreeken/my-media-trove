import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { tagService } from '@/services/tags'
import type { Tag } from '@/types'

export const useTagStore = defineStore('tags', () => {
  const tags = ref<Tag[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const systemTags = computed(() => tags.value.filter(t => t.is_system))
  const userTags = computed(() => tags.value.filter(t => !t.is_system))

  async function fetchTags() {
    try {
      loading.value = true
      error.value = null
      tags.value = await tagService.getTags()
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to load tags'
    } finally {
      loading.value = false
    }
  }

  async function createTag(data: { name: string; color?: string }) {
    try {
      loading.value = true
      error.value = null
      const tag = await tagService.createTag(data)
      tags.value = [...tags.value, tag]
      return tag
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to create tag'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateTag(id: string, data: { name?: string; color?: string }) {
    try {
      loading.value = true
      error.value = null
      const updated = await tagService.updateTag(id, data)
      const index = tags.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tags.value[index] = updated
      }
      return updated
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to update tag'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteTag(id: string) {
    try {
      loading.value = true
      error.value = null
      await tagService.deleteTag(id)
      tags.value = tags.value.filter(t => t.id !== id)
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Failed to delete tag'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    tags,
    loading,
    error,
    systemTags,
    userTags,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
  }
})
