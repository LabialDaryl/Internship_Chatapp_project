<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-lg p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-4">
      
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div class="flex items-center space-x-2 text-violet-400">
          <span class="text-lg">🔍</span>
          <h3 class="text-base font-bold text-slate-100">Search In Chat</h3>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-200">✕</button>
      </div>

      <!-- Search Field -->
      <div class="relative">
        <input
          v-model="query"
          @input="handleSearch"
          type="text"
          placeholder="Search message history..."
          class="w-full px-4 py-3 bg-slate-800 border border-slate-700/80 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-violet-500"
          autofocus
        />
        <span v-if="loading" class="absolute right-3 top-3 text-xs text-slate-400 animate-pulse">Searching...</span>
      </div>

      <!-- Results List -->
      <div class="max-h-64 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
        <div v-if="results.length === 0 && query.trim() && !loading" class="text-center py-6 text-xs text-slate-500">
          No matching messages found.
        </div>

        <div
          v-for="msg in results"
          :key="msg.id"
          @click="selectResult(msg)"
          class="p-3 bg-slate-800/40 border border-slate-800 hover:bg-violet-600/15 hover:border-violet-500/30 rounded-xl cursor-pointer transition-all space-y-1 group"
        >
          <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-violet-300 group-hover:text-violet-200">{{ msg.sender?.name || 'User' }}</span>
            <span class="text-[10px] text-slate-500">{{ formatTime(msg.created_at) }}</span>
          </div>
          <p class="text-xs text-slate-300 truncate" v-html="highlight(msg.body)"></p>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useChatStore } from '../../stores/chat'
import messagesService from '../../services/messages'

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'select'])

const chatStore = useChatStore()
const query = ref('')
const results = ref([])
const loading = ref(false)
let debounceTimer = null

function handleSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (!query.value.trim()) {
    results.value = []
    return
  }

  debounceTimer = setTimeout(async () => {
    if (!chatStore.activeConversation) return
    loading.value = true
    try {
      results.value = await messagesService.searchMessages(chatStore.activeConversation.id, query.value.trim())
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 300)
}

function selectResult(msg) {
  emit('select', msg)
  emit('close')
}

function highlight(text) {
  if (!query.value.trim() || !text) return text
  const q = query.value.trim()
  const regex = new RegExp(`(${q})`, 'gi')
  return text.replace(regex, '<mark class="bg-violet-500/40 text-violet-200 font-semibold px-0.5 rounded">$1</mark>')
}

function formatTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
