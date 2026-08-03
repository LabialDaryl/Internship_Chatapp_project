<template>
  <div v-if="show && caller" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-sm p-6 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl space-y-6 text-center">
      
      <!-- Animated Pulsing Call Icon -->
      <div class="relative mx-auto w-20 h-20 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full bg-violet-600/30 animate-ping"></div>
        <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
          {{ caller.name?.charAt(0) || 'C' }}
        </div>
      </div>

      <!-- Caller Details -->
      <div>
        <h3 class="text-lg font-bold text-slate-100">{{ caller.name || 'Incoming Call' }}</h3>
        <p class="text-xs text-violet-400 font-semibold mt-1">
          Incoming {{ callType === 'video' ? 'Video' : 'Audio' }} Call...
        </p>
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
defineProps({
  show: Boolean,
  caller: Object,
  callType: {
    type: String,
    default: 'video'
  }
})

const emit = defineEmits(['accept', 'decline'])
</script>
