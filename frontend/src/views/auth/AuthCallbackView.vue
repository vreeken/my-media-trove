<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

onMounted(async () => {
  const success = route.query.success === 'true'
  const error = route.query.error as string | undefined

  if (success) {
    await authStore.checkAuth()
    router.push('/')
  } else if (error) {
    router.push({ name: 'login', query: { error } })
  } else {
    router.push({ name: 'login' })
  }
})
</script>

<template>
  <div class="card p-8 text-center">
    <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"></div>
    <p class="text-dark-300">Completing authentication...</p>
  </div>
</template>
