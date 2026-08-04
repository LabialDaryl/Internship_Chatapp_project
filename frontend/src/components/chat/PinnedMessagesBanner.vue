<template>
  <div v-if="pinnedMessages && pinnedMessages.length > 0" class="bg-white/90 dark:bg-slate-900/90 border-b border-slate-200 dark:border-slate-800/80 px-6 py-2 flex items-center justify-between z-20 backdrop-blur-md">
    <div class="flex items-center space-x-3 truncate cursor-pointer" @click="emit('jump', currentPinned)">
      <span class="text-violet-600 dark:text-violet-400 text-sm font-bold animate-pulse">📌</span>
      <div class="truncate text-xs">
        <span class="font-bold text-violet-600 dark:text-violet-300 mr-2">{{ currentPinned.sender?.name || 'Pinned' }}:</span>
        <span class="text-slate-700 dark:text-slate-300 italic">{{ currentPinned.body }}</span>
      </div>
    </div>

    <div class="flex items-center space-x-3 text-xs text-slate-500 dark:text-slate-400 shrink-0">
      <span v-if="pinnedMessages.length > 1" class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full font-mono text-[10px]">
        {{ currentIndex + 1 }} of {{ pinnedMessages.length }}
      </span>
      <button v-if="pinnedMessages.length > 1" @click="nextPinned" class="hover:text-slate-700 dark:hover:text-slate-200">➡️</button>
      <button @click="emit('unpin', currentPinned)" title="Unpin message" class="hover:text-rose-500">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  pinnedMessages: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['jump', 'unpin'])

const currentIndex = ref(0)

const currentPinned = computed(() => {
  if (!props.pinnedMessages || props.pinnedMessages.length === 0) return null
  return props.pinnedMessages[currentIndex.value % props.pinnedMessages.length]
})

function nextPinned() {
  if (props.pinnedMessages.length > 0) {
    currentIndex.value = (currentIndex.value + 1) % props.pinnedMessages.length
  }
}
</script>
