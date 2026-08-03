<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="w-full max-w-md glass-panel p-6 rounded-2xl shadow-2xl space-y-4 animate-fade-in-up">
      <div class="flex justify-between items-center border-b border-slate-800 pb-3">
        <h3 class="text-lg font-bold text-slate-100">Start a Conversation</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-200">✕</button>
      </div>

      <!-- Search Field -->
      <Input
        v-model="query"
        placeholder="Search by name or @username..."
        @input="handleSearch"
      />

      <!-- Search Results -->
      <div class="max-h-60 overflow-y-auto space-y-1">
        <div v-if="loading" class="text-center p-4 text-xs text-slate-500">Searching...</div>
        <div v-else-if="query && chatStore.searchResults.length === 0" class="text-center p-4 text-xs text-slate-500">
          No users found.
        </div>

        <div
          v-for="user in chatStore.searchResults"
          :key="user.id"
          @click="startChat(user.id)"
          class="p-3 flex items-center gap-3 rounded-xl hover:bg-slate-800 cursor-pointer transition-all"
        >
          <Avatar :name="user.name" :src="user.avatar_url" size="sm" />
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-slate-200 truncate">{{ user.name }}</h4>
            <p class="text-xs text-slate-400">@{{ user.username }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useChatStore } from '../../stores/chat'
import Input from '../base/Input.vue'
import Avatar from '../base/Avatar.vue'

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const chatStore = useChatStore()
const query = ref('')
const loading = ref(false)

let searchTimeout = null

const handleSearch = () => {
  clearTimeout(searchTimeout)
  if (!query.value.trim()) {
    chatStore.searchResults = []
    return
  }
  loading.value = true
  searchTimeout = setTimeout(async () => {
    await chatStore.searchContacts(query.value)
    loading.value = false
  }, 300)
}

const startChat = async (userId) => {
  try {
    await chatStore.startDirectChat(userId)
    emit('close')
  } catch (err) {
    // Handled in store
  }
}
</script>
