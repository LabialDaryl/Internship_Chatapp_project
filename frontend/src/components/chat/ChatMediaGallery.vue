<template>
  <div v-if="show" class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-slate-900 border-l border-slate-800 shadow-2xl flex flex-col animate-slide-in">
    
    <!-- Header Bar -->
    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
      <div class="flex items-center space-x-2 text-violet-400">
        <span class="text-lg">📁</span>
        <h3 class="text-sm font-bold text-slate-100">Conversation Media & Files</h3>
      </div>
      <button @click="emit('close')" class="text-slate-400 hover:text-slate-200">✕</button>
    </div>

    <!-- Category Tabs -->
    <div class="flex border-b border-slate-800 bg-slate-950/60 p-2">
      <button
        @click="activeTab = 'images'"
        :class="['flex-1 py-1.5 text-xs font-bold rounded-xl transition-all', activeTab === 'images' ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-slate-200']"
      >
        🖼️ Photos
      </button>
      <button
        @click="activeTab = 'files'"
        :class="['flex-1 py-1.5 text-xs font-bold rounded-xl transition-all', activeTab === 'files' ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-slate-200']"
      >
        📄 Files
      </button>
      <button
        @click="activeTab = 'audio'"
        :class="['flex-1 py-1.5 text-xs font-bold rounded-xl transition-all', activeTab === 'audio' ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-slate-200']"
      >
        🎙️ Voice Notes
      </button>
    </div>

    <!-- Media List Content -->
    <div class="flex-1 p-4 overflow-y-auto custom-scrollbar">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400 animate-pulse">
        Loading media gallery...
      </div>

      <div v-else-if="filteredItems.length === 0" class="py-12 text-center text-xs text-slate-500">
        No shared {{ activeTab }} in this conversation.
      </div>

      <!-- IMAGES TAB -->
      <div v-else-if="activeTab === 'images'" class="grid grid-cols-2 gap-3">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="h-32 rounded-2xl overflow-hidden border border-slate-800 group relative cursor-pointer bg-slate-950"
          @click="emit('open-image', item.body)"
        >
          <img :src="item.body" alt="Media" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
        </div>
      </div>

      <!-- FILES TAB -->
      <div v-else-if="activeTab === 'files'" class="space-y-2">
        <a
          v-for="item in filteredItems"
          :key="item.id"
          :href="item.body"
          target="_blank"
          class="flex items-center space-x-3 p-3 bg-slate-800/50 border border-slate-800 hover:border-violet-500/50 rounded-2xl transition-all group"
        >
          <div class="w-10 h-10 rounded-xl bg-violet-600/20 text-violet-400 flex items-center justify-center font-bold">📄</div>
          <div class="flex-1 truncate">
            <p class="text-xs font-bold text-slate-200 group-hover:text-violet-300 truncate">{{ getFileName(item.body) }}</p>
            <p class="text-[10px] text-slate-500">Shared by {{ item.sender?.name }}</p>
          </div>
        </a>
      </div>

      <!-- AUDIO TAB -->
      <div v-else-if="activeTab === 'audio'" class="space-y-3">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="p-3 bg-slate-800/50 border border-slate-800 rounded-2xl space-y-2"
        >
          <p class="text-[10px] text-slate-400">Voice Note by {{ item.sender?.name }}</p>
          <audio :src="item.body" controls class="w-full h-8"></audio>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import messagesService from '../../services/messages'

const props = defineProps({
  show: Boolean,
  conversationId: Number
})

const emit = defineEmits(['close', 'open-image'])

const activeTab = ref('images')
const items = ref([])
const loading = ref(false)

watch(() => props.show, async (val) => {
  if (val && props.conversationId) {
    loading.value = true
    try {
      items.value = await messagesService.getConversationMedia(props.conversationId)
    } catch {
      items.value = []
    } finally {
      loading.value = false
    }
  }
})

const filteredItems = computed(() => {
  if (activeTab.value === 'images') {
    return items.value.filter(i => i.type === 'image')
  } else if (activeTab.value === 'files') {
    return items.value.filter(i => i.type === 'file')
  } else if (activeTab.value === 'audio') {
    return items.value.filter(i => i.type === 'audio')
  }
  return []
})

function getFileName(url) {
  if (!url) return 'File'
  return url.split('/').pop() || 'File'
}
</script>
