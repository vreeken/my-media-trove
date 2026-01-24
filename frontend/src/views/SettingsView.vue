<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/services/auth'

const router = useRouter()
const authStore = useAuthStore()

const showPasswordModal = ref(false)
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordError = ref('')
const passwordLoading = ref(false)

const profileForm = reactive({
  name: authStore.user?.name || '',
})
const profileLoading = ref(false)
const profileSuccess = ref(false)

async function updateProfile() {
  profileLoading.value = true
  profileSuccess.value = false
  try {
    await authStore.updateProfile({
      name: profileForm.name,
    })
    profileSuccess.value = true
    setTimeout(() => profileSuccess.value = false, 3000)
  } catch {
    // Error handled in store
  } finally {
    profileLoading.value = false
  }
}

async function updatePassword() {
  passwordLoading.value = true
  passwordError.value = ''
  try {
    await authService.updatePassword(passwordForm)
    showPasswordModal.value = false
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    passwordError.value = err.response?.data?.message || 'Failed to update password'
  } finally {
    passwordLoading.value = false
  }
}

async function logout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <h1 class="text-2xl font-bold text-white">Settings</h1>

    <!-- Profile Section -->
    <div class="card p-6 space-y-4">
      <h2 class="text-lg font-semibold text-white">Profile</h2>

      <form @submit.prevent="updateProfile" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-dark-300 mb-1 block">Name</label>
          <input v-model="profileForm.name" type="text" class="input" />
        </div>

        <div>
          <label class="text-sm font-medium text-dark-300 mb-1 block">Email</label>
          <p class="py-2 px-3 bg-dark-800 rounded-lg text-dark-400 border border-dark-600">
            {{ authStore.user?.email }}
          </p>
          <p class="text-xs text-dark-500 mt-1">Email cannot be changed after account creation</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" :disabled="profileLoading" class="btn-primary">
            Save Changes
          </button>
          <span v-if="profileSuccess" class="text-green-400 text-sm">Saved!</span>
        </div>
      </form>
    </div>

    <!-- Security Section -->
    <div class="card p-6 space-y-4">
      <h2 class="text-lg font-semibold text-white">Security</h2>

      <button @click="showPasswordModal = true" class="btn-secondary">
        Change Password
      </button>
    </div>

    <!-- Management Links -->
    <div class="card p-6 space-y-3">
      <h2 class="text-lg font-semibold text-white mb-4">Manage</h2>

      <router-link to="/settings/locations" class="flex items-center justify-between p-3 -mx-3 rounded-lg hover:bg-dark-700 transition-colors">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span class="text-white">Locations</span>
        </div>
        <svg class="w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </router-link>

      <router-link to="/settings/tags" class="flex items-center justify-between p-3 -mx-3 rounded-lg hover:bg-dark-700 transition-colors">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <span class="text-white">Tags</span>
        </div>
        <svg class="w-5 h-5 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </router-link>
    </div>

    <!-- Logout -->
    <div class="card p-6">
      <button @click="logout" class="btn-danger w-full">
        Sign Out
      </button>
    </div>

    <!-- Password Modal -->
    <Teleport to="body">
      <div
        v-if="showPasswordModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showPasswordModal = false"
      >
        <div class="card p-6 w-full max-w-md animate-fade-in">
          <h3 class="text-lg font-semibold text-white mb-4">Change Password</h3>

          <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">Current Password</label>
              <input v-model="passwordForm.current_password" type="password" class="input" required />
            </div>

            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">New Password</label>
              <input v-model="passwordForm.password" type="password" class="input" required />
            </div>

            <div>
              <label class="text-sm font-medium text-dark-300 mb-1 block">Confirm New Password</label>
              <input v-model="passwordForm.password_confirmation" type="password" class="input" required />
            </div>

            <p v-if="passwordError" class="text-sm text-red-400">{{ passwordError }}</p>

            <div class="flex gap-3">
              <button type="submit" :disabled="passwordLoading" class="btn-primary flex-1">
                Update Password
              </button>
              <button type="button" @click="showPasswordModal = false" class="btn-secondary flex-1">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
