<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-white text-lg font-bold shadow-lg shadow-violet-500/20">
            {{ user?.name?.charAt(0) || user?.username?.charAt(0) || 'U' }}
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-100">{{ user?.name || user?.username }}</h3>
            <p class="text-xs text-slate-400">@{{ user?.username }}</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex space-x-2 border-b border-slate-800/80 pb-2">
        <button
          @click="activeTab = 'profile'"
          class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
          :class="activeTab === 'profile' ? 'bg-violet-600/20 text-violet-300 border border-violet-500/30' : 'text-slate-400 hover:text-slate-200'"
        >
          Profile Details
        </button>
        <button
          @click="activeTab = 'password'"
          class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
          :class="activeTab === 'password' ? 'bg-violet-600/20 text-violet-300 border border-violet-500/30' : 'text-slate-400 hover:text-slate-200'"
        >
          Security & Password
        </button>
      </div>

      <!-- Alert Status -->
      <div v-if="successMsg" class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-300">
        {{ successMsg }}
      </div>
      <div v-if="errorMsg" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-xs text-rose-300">
        {{ errorMsg }}
      </div>

      <!-- Tab 1: Profile Details -->
      <form v-if="activeTab === 'profile'" @submit.prevent="handleSaveProfile" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Display Name</label>
          <input
            v-model="profileForm.name"
            type="text"
            required
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Username</label>
          <input
            v-model="profileForm.username"
            type="text"
            required
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Email Address</label>
          <input
            v-model="profileForm.email"
            type="email"
            required
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>

        <div class="pt-2 flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl shadow-lg shadow-violet-600/25 disabled:opacity-50 transition-all"
          >
            {{ loading ? 'Saving...' : 'Save Profile' }}
          </button>
        </div>
      </form>

      <!-- Tab 2: Security & Password -->
      <form v-if="activeTab === 'password'" @submit.prevent="handleChangePassword" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Current Password</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            required
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">New Password (8+ characters)</label>
          <input
            v-model="passwordForm.password"
            type="password"
            required
            minlength="8"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1">Confirm New Password</label>
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            required
            minlength="8"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all"
          />
        </div>

        <div class="pt-2 flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl shadow-lg shadow-violet-600/25 disabled:opacity-50 transition-all"
          >
            {{ loading ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const activeTab = ref('profile')
const loading = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const profileForm = reactive({
  name: '',
  username: '',
  email: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

watch(() => props.show, (newVal) => {
  if (newVal && user.value) {
    profileForm.name = user.value.name || ''
    profileForm.username = user.value.username || ''
    profileForm.email = user.value.email || ''
    successMsg.value = ''
    errorMsg.value = ''
  }
}, { immediate: true })

async function handleSaveProfile() {
  loading.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    await authStore.updateProfile({ ...profileForm })
    successMsg.value = 'Profile updated successfully!'
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to update profile'
  } finally {
    loading.value = false
  }
}

async function handleChangePassword() {
  loading.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    await authStore.updatePassword({ ...passwordForm })
    successMsg.value = 'Password updated successfully!'
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to update password'
  } finally {
    loading.value = false
  }
}
</script>
