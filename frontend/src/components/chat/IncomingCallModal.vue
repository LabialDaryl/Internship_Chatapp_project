<template>
  <div v-if="show && caller" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-sm p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl space-y-6 text-center">
      
      <!-- Animated Pulsing Call Icon -->
      <div class="relative mx-auto w-24 h-24 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full bg-violet-600/30 animate-ping"></div>
        <Avatar :name="caller.name || 'Caller'" :src="caller.avatar_url" size="lg" />
        <span class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-violet-600 text-white flex items-center justify-center text-xs shadow-md">
          {{ callType === 'video' ? '📹' : '📞' }}
        </span>
      </div>

      <!-- Caller Details & Incoming Call Indicator -->
      <div class="space-y-1">
        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-violet-600/10 text-violet-600 dark:text-violet-400 border border-violet-500/20 animate-pulse">
          🔔 Incoming {{ callType === 'video' ? 'Video' : 'Audio' }} Call...
        </span>
        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 pt-1">{{ caller.name || 'Incoming Call' }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">@{{ caller.username || 'user' }}</p>
      </div>

      <!-- Action Buttons (Accept & Decline) -->
      <div class="flex items-center justify-center space-x-6 pt-2">
        <!-- Decline Call -->
        <button
          @click="emit('decline')"
          class="w-14 h-14 rounded-full bg-rose-600 hover:bg-rose-500 text-white flex items-center justify-center text-xl shadow-lg shadow-rose-600/30 transform hover:scale-110 transition-all"
          title="Decline Call"
        >
          📞
        </button>

        <!-- Accept Call -->
        <button
          @click="emit('accept')"
          class="w-14 h-14 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center text-xl shadow-lg shadow-emerald-600/30 transform hover:scale-110 transition-all animate-bounce"
          title="Accept Call"
        >
          {{ callType === 'video' ? '📹' : '📞' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'
import { useNotificationSound } from '../../composables/useNotificationSound'
import Avatar from '../base/Avatar.vue'

const props = defineProps({
  show: Boolean,
  caller: Object,
  callType: {
    type: String,
    default: 'video'
  }
})

const emit = defineEmits(['accept', 'decline'])

const { playRingtone, stopRingtone } = useNotificationSound()

watch(() => props.show, (newVal) => {
  if (newVal) {
    playRingtone()
  } else {
    stopRingtone()
  }
}, { immediate: true })
</script>
