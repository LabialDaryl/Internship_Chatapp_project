<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-sm p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl space-y-5 text-center">
      
      <!-- Icon -->
      <div class="w-14 h-14 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center text-2xl mx-auto shadow-md shadow-rose-500/10">
        🚪
      </div>

      <!-- Text Header -->
      <div class="space-y-1">
        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Log Out To EsmiringHOY?</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Are you sure you want to end your current session? You will need to sign in again to access your messages.
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-3 pt-2">
        <button
          @click="emit('close')"
          class="flex-1 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-slate-200 dark:border-slate-700"
        >
          Cancel
        </button>
        <button
          @click="emit('confirm')"
          class="flex-1 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-lg shadow-rose-600/30 transition-all"
        >
          LOGOUT
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'confirm'])
</script>
