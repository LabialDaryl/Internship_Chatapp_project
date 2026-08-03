<template>
  <div v-if="show && message" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-sm p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center space-x-3 text-rose-400">
        <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-lg font-bold">
          🗑️
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-100">Delete Message?</h3>
          <p class="text-xs text-slate-400">Choose how you want to delete this message</p>
        </div>
      </div>

      <!-- Message Preview Box -->
      <div class="p-3 bg-slate-800/60 border border-slate-700/60 rounded-xl text-xs text-slate-300 italic truncate">
        "{{ message.body }}"
      </div>

      <!-- Delete Options -->
      <div class="space-y-2">
        <button
          v-if="isOwn || isAdmin"
          @click="confirmDelete('everyone')"
          class="w-full p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 hover:bg-rose-500/25 text-rose-200 text-xs font-semibold flex items-center justify-between transition-all group"
        >
          <div class="flex items-center space-x-2">
            <span>🌐</span>
            <span>Delete for Everyone</span>
          </div>
          <span class="text-[10px] text-rose-300 opacity-80 group-hover:opacity-100">Removes for all members</span>
        </button>

        <button
          @click="confirmDelete('me')"
          class="w-full p-3 rounded-xl bg-slate-800/80 border border-slate-700/80 hover:bg-slate-800 text-slate-200 text-xs font-semibold flex items-center justify-between transition-all group"
        >
          <div class="flex items-center space-x-2">
            <span>👤</span>
            <span>Delete for Me</span>
          </div>
          <span class="text-[10px] text-slate-400 group-hover:text-slate-300">Removes from your view only</span>
        </button>
      </div>

      <!-- Cancel Button -->
      <div class="pt-2 flex justify-end">
        <button
          @click="emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-xl transition-all"
        >
          Cancel
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  show: Boolean,
  message: Object,
  isOwn: Boolean,
  isAdmin: Boolean,
})

const emit = defineEmits(['close', 'confirm'])

function confirmDelete(type) {
  emit('confirm', { messageId: props.message.id, type })
  emit('close')
}
</script>
