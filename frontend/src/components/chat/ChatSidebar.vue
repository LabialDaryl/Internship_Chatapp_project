<template>
  <aside class="flex flex-col justify-between items-center py-4 px-2 w-16 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 z-20">
    <!-- Top Section: Logo + Primary Actions -->
    <div class="flex flex-col items-center gap-5">
      <!-- App Logo -->
      <div title="EsmiringHOY — CONNECT & CHAT" class="cursor-pointer transform hover:scale-105 transition-transform">
        <AppLogo size="sm" :iconOnly="true" />
      </div>

      <!-- New Direct Chat Button -->
      <button
        @click="$emit('open-new-chat')"
        class="w-10 h-10 rounded-xl bg-violet-600/10 dark:bg-violet-600/20 text-violet-600 dark:text-violet-400 hover:bg-violet-600 hover:text-white transition-all flex items-center justify-center border border-violet-500/20 shadow-sm"
        title="Start Direct Chat"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </button>

      <!-- New Group Chat Button -->
      <button
        @click="$emit('open-create-group')"
        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-violet-600 hover:text-white text-slate-600 dark:text-slate-400 transition-all flex items-center justify-center border border-slate-200 dark:border-slate-700/60 shadow-sm"
        title="Create Group Chat"
      >
        <span class="text-base">👥</span>
      </button>
    </div>

    <!-- Bottom Section: Profile Popover Trigger only -->
    <div class="flex flex-col items-center gap-4 relative">

      <!-- User Avatar → opens the profile popover (Settings(theme, etc.),Logout all live here) -->
      <div class="relative">
        <div
          @click="showProfilePopover = !showProfilePopover"
          class="cursor-pointer transform transition-transform hover:scale-110"
          title="Account Menu — Settings, Theme & More"
        >
          <Avatar
            :name="authStore.user?.name"
            :src="authStore.user?.avatar_url"
            size="sm"
            showStatus
            :isOnline="true"
          />
        </div>

        <!-- Messenger-Style Profile Popover Menu -->
        <div
          v-if="showProfilePopover"
          class="absolute bottom-0 left-14 z-50 w-60 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl space-y-3 animate-fade-in"
          @click.stop
        >
          <!-- User Info Banner -->
          <div class="flex items-center space-x-3 pb-2 border-b border-slate-200 dark:border-slate-800">
            <Avatar :name="authStore.user?.name" :src="authStore.user?.avatar_url" size="md" showStatus :isOnline="true" />
            <div class="min-w-0 flex-1">
              <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ authStore.user?.name }}</h4>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">@{{ authStore.user?.username }}</p>
              <span class="inline-block mt-0.5 text-[9px] font-semibold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded-md">🟢 Active Now</span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="space-y-1 text-xs">
            <!-- Edit Profile -->
            <button
              @click="showProfilePopover = false; $emit('open-profile')"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2 font-medium transition-colors"
            >
              <span>👤</span> <span>Edit Profile</span>
            </button>

            <!-- Settings -->
            <button
              @click="showProfilePopover = false; $emit('open-settings')"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2 font-medium transition-colors"
            >
              <span>⚙️</span> <span>Settings</span>
            </button>

            <!-- Divider -->
            <div class="my-1 border-t border-slate-200 dark:border-slate-800"></div>

            <!-- Log Out -->
            <button
              @click="showProfilePopover = false; $emit('open-logout-modal')"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-600 dark:text-rose-400 flex items-center space-x-2 font-bold transition-colors"
            >
              <span>🚪</span> <span>Log Out</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useTheme } from '../../composables/useTheme'
import Avatar from '../base/Avatar.vue'
import AppLogo from '../base/AppLogo.vue'

defineEmits(['open-new-chat', 'open-create-group', 'open-profile', 'open-settings', 'open-logout-modal'])

const authStore = useAuthStore()
const { isDark, toggleDark } = useTheme()
const showProfilePopover = ref(false)
</script>
