<script setup lang="ts">
import type { MediaItem } from '@/types'

defineProps<{
  item: MediaItem
}>()

defineEmits<{
  click: []
}>()
</script>

<template>
  <button
    @click="$emit('click')"
    class="card-hover text-left group relative aspect-[2/3] overflow-hidden"
  >
    <!-- Poster -->
    <img
      v-if="item.poster_url"
      :src="item.poster_url"
      :alt="item.title"
      class="w-full h-full object-cover"
      loading="lazy"
    />
    <div
      v-else
      class="w-full h-full bg-dark-700 flex items-center justify-center"
    >
      <svg class="w-12 h-12 text-dark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="item.type_icon === 'film'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
        <path v-else-if="item.type_icon === 'tv'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
      </svg>
    </div>

    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

    <!-- Info Overlay -->
    <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform">
      <h3 class="font-medium text-white text-sm line-clamp-2">{{ item.title }}</h3>
      <div class="flex items-center gap-2 mt-1 text-xs text-dark-300">
        <span v-if="item.year">{{ item.year }}</span>
        <span v-if="item.rating" class="flex items-center gap-0.5">
          <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          {{ item.rating }}
        </span>
      </div>
    </div>

    <!-- Type Badge -->
    <div class="absolute top-2 left-2">
      <span class="badge-primary text-xs">{{ item.type_label }}</span>
    </div>

    <!-- Custom Badge -->
    <div v-if="item.is_custom" class="absolute top-2 right-2">
      <span class="badge bg-purple-500/20 text-purple-400 text-xs">Custom</span>
    </div>
  </button>
</template>
