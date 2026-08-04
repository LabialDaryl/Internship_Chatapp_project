<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-lg p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
          <div class="relative group cursor-pointer" @click="activeTab = 'profile'">
            <Avatar :name="user?.name" :src="profileForm.avatar_url || user?.avatar_url" size="lg" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ user?.name || user?.username }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">@{{ user?.username }}</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button
          @click="activeTab = 'profile'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all"
          :class="activeTab === 'profile' ? 'bg-violet-600/10 text-violet-600 dark:bg-violet-600/20 dark:text-violet-300 border border-violet-500/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
        >
          👤 Profile Details & Bio
        </button>
        <button
          @click="activeTab = 'password'"
          class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all"
          :class="activeTab === 'password' ? 'bg-violet-600/10 text-violet-600 dark:bg-violet-600/20 dark:text-violet-300 border border-violet-500/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
        >
          🔒 Security & Password
        </button>
      </div>

      <!-- Alert Status -->
      <div v-if="successMsg" class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-600 dark:text-emerald-300 font-medium">
        {{ successMsg }}
      </div>
      <div v-if="errorMsg" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-xs text-rose-600 dark:text-rose-300 font-medium">
        {{ errorMsg }}
      </div>

      <!-- Tab 1: Profile Details & Bio -->
      <form v-if="activeTab === 'profile'" @submit.prevent="handleSaveProfile" class="space-y-4">
        
        <!-- Avatar Photo Customization -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Avatar Image URL</label>
          <div class="flex items-center space-x-2">
            <input
              v-model="profileForm.avatar_url"
              type="text"
              placeholder="Paste image link (e.g. https://...)"
              class="flex-1 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all placeholder-slate-400"
            />
            <button
              type="button"
              @click="profileForm.avatar_url = ''"
              class="px-2.5 py-2 text-xs font-semibold text-slate-500 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-xl transition-all"
            >
              Clear
            </button>
          </div>
        </div>

        <!-- Display Name -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Display Name</label>
          <input
            v-model="profileForm.name"
            type="text"
            required
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>

        <!-- Username -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Username</label>
          <input
            v-model="profileForm.username"
            type="text"
            required
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
          <input
            v-model="profileForm.email"
            type="email"
            required
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>

        <!-- Bio / Custom Status -->
        <div>
          <div class="flex justify-between items-center mb-1">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Bio / About Me</label>
            <span class="text-[10px] text-slate-400">{{ (profileForm.bio || '').length }} / 500</span>
          </div>
          <textarea
            v-model="profileForm.bio"
            rows="3"
            maxlength="500"
            placeholder="Write a short bio or status message to introduce yourself..."
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all placeholder-slate-400"
          ></textarea>
        </div>

        <div class="pt-2 flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl shadow-lg shadow-violet-600/25 disabled:opacity-50 transition-all"
          >
            {{ loading ? 'Saving...' : 'Save Profile' }}
          </button>
        </div>
      </form>

      <!-- Tab 2: Security & Password -->
      <form v-if="activeTab === 'password'" @submit.prevent="handleChangePassword" class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Current Password</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            required
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">New Password (8+ characters)</label>
          <input
            v-model="passwordForm.password"
            type="password"
            required
            minlength="8"
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Confirm New Password</label>
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            required
            minlength="8"
            class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
          />
        </div>

        <div class="pt-2 flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl shadow-lg shadow-violet-600/25 disabled:opacity-50 transition-all"
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
import Avatar from '../base/Avatar.vue'

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
  bio: '',
  avatar_url: '',
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
    profileForm.bio = user.value.bio || ''
    profileForm.avatar_url = user.value.avatar_url || ''
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
    successMsg.value = 'Profile details & Bio updated successfully!'
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
