<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const errors = ref<Record<string, string[]>>({})
const loading = ref(false)

async function handleSubmit() {
  try {
    loading.value = true
    errors.value = {}
    await authStore.login(form)
    const redirect = route.query.redirect as string || '/'
    router.push(redirect)
  } catch (e: unknown) {
    const err = e as { response?: { data?: { errors?: Record<string, string[]> } } }
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

function loginWithGoogle() {
  window.location.href = '/auth/google/redirect'
}
</script>

<template>
  <div class="card p-6">
    <h2 class="text-xl font-semibold text-white mb-6">Sign in to your account</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-dark-300 mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="input"
          :class="{ 'input-error': errors.email }"
          placeholder="you@example.com"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-400">{{ errors.email[0] }}</p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-dark-300 mb-1">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="input"
          :class="{ 'input-error': errors.password }"
          placeholder="••••••••"
        />
        <p v-if="errors.password" class="mt-1 text-sm text-red-400">{{ errors.password[0] }}</p>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center">
        <input
          id="remember"
          v-model="form.remember"
          type="checkbox"
          class="w-4 h-4 rounded border-dark-600 bg-dark-700 text-primary-600 focus:ring-primary-500 focus:ring-offset-dark-800"
        />
        <label for="remember" class="ml-2 text-sm text-dark-300">Remember me</label>
      </div>

      <!-- Error Message -->
      <p v-if="authStore.error" class="text-sm text-red-400">{{ authStore.error }}</p>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading"
        class="btn-primary w-full"
      >
        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Sign in
      </button>
    </form>

    <!-- Divider -->
    <div class="relative my-6">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-dark-600"></div>
      </div>
      <div class="relative flex justify-center text-sm">
        <span class="px-2 bg-dark-800 text-dark-400">Or continue with</span>
      </div>
    </div>

    <!-- Google Login -->
    <button
      @click="loginWithGoogle"
      type="button"
      class="btn-outline w-full"
    >
      <svg class="w-5 h-5" viewBox="0 0 24 24">
        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Sign in with Google
    </button>

    <!-- Register Link -->
    <p class="mt-6 text-center text-sm text-dark-400">
      Don't have an account?
      <router-link to="/register" class="text-primary-400 hover:text-primary-300">
        Sign up
      </router-link>
    </p>
  </div>
</template>
