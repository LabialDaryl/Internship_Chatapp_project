<template>
  <aside class="flex flex-col justify-between items-center py-4 px-2 w-16 bg-slate-900 border-r border-slate-800 text-slate-400">
    <div class="flex flex-col items-center gap-5">
      <!-- App Logo -->
      <div title="EsmiringHOY — CONNECT & CHAT" class="cursor-pointer transform hover:scale-105 transition-transform">
        <AppLogo size="sm" :iconOnly="true" />
      </div>

      <!-- New Direct Chat Button -->
      <button
        @click="$emit('open-new-chat')"
        class="w-10 h-10 rounded-xl bg-violet-600/15 text-violet-400 hover:bg-violet-600 hover:text-white transition-all flex items-center justify-center border border-violet-500/20"
        title="Start Direct Chat"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </button>

      <!-- New Group Chat Button -->
      <button
        @click="$emit('open-create-group')"
        class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-violet-600 text-slate-400 hover:text-white transition-all flex items-center justify-center border border-slate-700/60"
        title="Create Group Chat"
      >
        <span class="text-base">👥</span>
      </button>
    </div>

    <div class="flex flex-col items-center gap-4">
      <!-- Theme Toggle -->
      <button
        @click="toggleDark"
        class="w-10 h-10 rounded-xl hover:bg-slate-800 text-slate-400 hover:text-slate-200 transition-all flex items-center justify-center"
        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
      >
        <span v-if="isDark">🌙</span>
        <span v-else>☀️</span>
      </button>

      <!-- User Avatar (Opens Profile Modal) -->
      <div @click="$emit('open-profile')" class="cursor-pointer transform transition-transform hover:scale-110" title="Click to view & edit Profile Settings">
        <Avatar
          :name="authStore.user?.name"
          :src="authStore.user?.avatar_url"
          size="sm"
          showStatus
          :isOnline="true"
        />
      </div>

      <!-- Logout Button -->
      <button
        @click="handleLogout"
        class="w-10 h-10 rounded-xl hover:bg-red-500/10 text-slate-400 hover:text-red-400 transition-all flex items-center justify-center"
        title="Logout"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5"></path>
        </svg>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import { useTheme } from '../../composables/useTheme'
import { useRouter } from 'vue-router'
import Avatar from '../base/Avatar.vue'
import AppLogo from '../base/AppLogo.vue'

defineEmits(['open-new-chat', 'open-create-group', 'open-profile'])

const authStore = useAuthStore()
const { isDark, toggleDark } = useTheme()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>
