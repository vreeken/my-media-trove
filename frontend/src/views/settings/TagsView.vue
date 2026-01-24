<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useTagStore } from '@/stores/tags'
import type { Tag } from '@/types'

const router = useRouter()
const tagStore = useTagStore()

const showModal = ref(false)
const editingTag = ref<Tag | null>(null)
const form = reactive({
  name: '',
  color: '#3B82F6',
})
const deleteConfirmId = ref<string | null>(null)

const colorOptions = [
  '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16',
  '#22C55E', '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9',
  '#3B82F6', '#6366F1', '#8B5CF6', '#A855F7', '#D946EF',
  '#EC4899', '#F43F5E',
]

onMounted(async () => {
  await tagStore.fetchTags()
})

function openCreateModal() {
  editingTag.value = null
  form.name = ''
  form.color = '#3B82F6'
  showModal.value = true
}

function openEditModal(tag: Tag) {
  editingTag.value = tag
  form.name = tag.name
  form.color = tag.color || '#3B82F6'
  showModal.value = true
}

async function saveTag() {
  if (editingTag.value) {
    await tagStore.updateTag(editingTag.value.id, {
      name: form.name,
      color: form.color,
    })
  } else {
    await tagStore.createTag({
      name: form.name,
      color: form.color,
    })
  }
  showModal.value = false
}

async function deleteTag(id: string) {
  await tagStore.deleteTag(id)
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
        <h1 class="text-2xl font-bold text-white">Tags</h1>
        <p class="text-dark-400">Organize your media with custom tags</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add
      </button>
    </div>

    <!-- Loading -->
    <div v-if="tagStore.loading && tagStore.tags.length === 0" class="flex items-center justify-center py-12">
      <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- System Tags -->
    <div v-if="tagStore.systemTags.length > 0">
      <h2 class="text-sm font-medium text-dark-400 mb-3">System Tags</h2>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="tag in tagStore.systemTags"
          :key="tag.id"
          class="px-3 py-1.5 rounded-lg text-sm text-white"
          :style="{ backgroundColor: tag.color || '#3b82f6' }"
        >
          {{ tag.name }}
        </span>
      </div>
    </div>

    <!-- User Tags -->
    <div>
      <h2 class="text-sm font-medium text-dark-400 mb-3">Your Tags</h2>
      
      <div v-if="tagStore.userTags.length === 0" class="text-center py-8">
        <p class="text-dark-400 mb-4">No custom tags yet</p>
        <button @click="openCreateModal" class="btn-secondary">Create Tag</button>
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="tag in tagStore.userTags"
          :key="tag.id"
          class="card p-3 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <span
              class="w-4 h-4 rounded-full"
              :style="{ backgroundColor: tag.color || '#3b82f6' }"
            ></span>
            <span class="text-white">{{ tag.name }}</span>
          </div>
          <div class="flex gap-2">
            <button @click="openEditModal(tag)" class="btn-ghost btn-sm">
              Edit
            </button>
            <button @click="deleteConfirmId = tag.id" class="btn-ghost btn-sm text-red-400">
              Delete
            </button>
          </div>
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
            {{ editingTag ? 'Edit Tag' : 'Create Tag' }}
          </h3>

          <form @submit.prevent="saveTag" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">Name</label>
              <input v-model="form.name" type="text" class="input" placeholder="e.g., Must Watch" required />
            </div>

            <div>
              <label class="text-sm font-medium text-dark-300 mb-2 block">Color</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colorOptions"
                  :key="color"
                  type="button"
                  @click="form.color = color"
                  class="w-8 h-8 rounded-full transition-transform"
                  :class="{ 'ring-2 ring-white ring-offset-2 ring-offset-dark-800 scale-110': form.color === color }"
                  :style="{ backgroundColor: color }"
                ></button>
              </div>
            </div>

            <div class="flex gap-3 pt-2">
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
          <h3 class="text-lg font-semibold text-white mb-2">Delete Tag?</h3>
          <p class="text-dark-400 mb-6">This tag will be removed from all media items.</p>
          <div class="flex gap-3">
            <button @click="deleteTag(deleteConfirmId!)" class="btn-danger flex-1">Delete</button>
            <button @click="deleteConfirmId = null" class="btn-secondary flex-1">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
