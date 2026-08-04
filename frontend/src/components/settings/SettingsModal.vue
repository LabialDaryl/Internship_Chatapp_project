<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-md p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl space-y-6">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-violet-600/10 text-violet-600 dark:text-violet-400 flex items-center justify-center text-lg font-bold">
            ⚙️
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">App Settings</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Preferences & customizations</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Settings List -->
      <div class="space-y-4">
        
        <!-- Appearance / Theme Selector -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <span class="text-xl">{{ isDark ? '🌙' : '☀️' }}</span>
            <div>
              <p class="text-xs font-bold text-slate-900 dark:text-slate-200">Appearance</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ isDark ? 'Dark Mode (Vibrant Violet)' : 'Light Mode (Messenger White)' }}
              </p>
            </div>
          </div>
          <button
            @click="toggleDark"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border"
            :class="isDark ? 'bg-violet-600 text-white border-violet-500 shadow-md' : 'bg-slate-200 text-slate-800 border-slate-300 hover:bg-slate-300'"
          >
            {{ isDark ? 'Dark Mode' : 'Light Mode' }}
          </button>
        </div>

        <!-- Notification Chimes -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <span class="text-xl">🔔</span>
            <div>
              <p class="text-xs font-bold text-slate-900 dark:text-slate-200">Message Notifications</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Play audio chime on incoming messages</p>
            </div>
          </div>
          <button
            @click="soundEnabled = !soundEnabled"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border"
            :class="soundEnabled ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-200 text-slate-600 border-slate-300'"
          >
            {{ soundEnabled ? 'Enabled' : 'Muted' }}
          </button>
        </div>

        <!-- Account Profile Customization Trigger -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <span class="text-xl">👤</span>
            <div>
              <p class="text-xs font-bold text-slate-900 dark:text-slate-200">Profile Details</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Update name, avatar, and password</p>
            </div>
          </div>
          <button
            @click="openProfileModal"
            class="px-3 py-1.5 rounded-xl text-xs font-bold text-violet-600 dark:text-violet-300 bg-violet-50 dark:bg-violet-600/20 border border-violet-200 dark:border-violet-500/30 hover:bg-violet-100 transition-all"
          >
            Edit
          </button>
        </div>

      </div>

      <!-- Footer -->
      <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
        <button
          @click="emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all"
        >
          Done
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useTheme } from '../../composables/useTheme'

defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'open-profile'])

const { isDark, toggleDark } = useTheme()
const soundEnabled = ref(true)

function openProfileModal() {
  emit('close')
  emit('open-profile')
}
</script>
