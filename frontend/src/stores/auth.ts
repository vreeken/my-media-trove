import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth'
import type { User, LoginCredentials, RegisterData } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const initialized = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)

  async function checkAuth() {
    if (initialized.value) return

    try {
      loading.value = true
      user.value = await authService.getUser()
    } catch {
      user.value = null
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function login(credentials: LoginCredentials) {
    try {
      loading.value = true
      error.value = null
      const response = await authService.login(credentials)
      user.value = response.user
      return response
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Login failed'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(data: RegisterData) {
    try {
      loading.value = true
      error.value = null
      const response = await authService.register(data)
      user.value = response.user
      return response
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Registration failed'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      loading.value = true
      await authService.logout()
    } finally {
      user.value = null
      loading.value = false
    }
  }

  async function updateProfile(data: Partial<User>) {
    try {
      loading.value = true
      error.value = null
      user.value = await authService.updateProfile(data)
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } }
      error.value = err.response?.data?.message || 'Update failed'
      throw e
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    user,
    loading,
    initialized,
    error,
    isAuthenticated,
    checkAuth,
    login,
    register,
    logout,
    updateProfile,
    clearError,
  }
})
