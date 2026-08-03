<template>
  <div class="flex flex-col h-full bg-slate-900/40 border-r border-slate-800/80 w-full md:w-80 lg:w-96">
    <!-- Header -->
    <div class="p-4 border-b border-slate-800 flex justify-between items-center">
      <h2 class="text-xl font-bold text-slate-100">Messages</h2>
      <button
        @click="$emit('open-new-chat')"
        class="p-2 rounded-lg bg-primary-500/10 text-primary-400 hover:bg-primary-500 hover:text-white transition-all text-xs font-semibold flex items-center gap-1"
      >
        <span>+ New Chat</span>
      </button>
    </div>

    <!-- Search Input -->
    <div class="p-3">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="Filter chats..."
        class="input-base text-sm py-1.5"
      />
    </div>

    <!-- Loading State -->
    <div v-if="chatStore.loading" class="flex-1 flex items-center justify-center text-slate-500 text-sm">
      Loading chats...
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredConversations.length === 0" class="flex-1 flex flex-col items-center justify-center p-6 text-center text-slate-500 space-y-3">
      <p class="text-sm">No conversations found</p>
      <button @click="$emit('open-new-chat')" class="text-xs text-primary-400 hover:underline">
        Start a new chat
      </button>
    </div>

    <!-- Conversation List -->
    <div v-else class="flex-1 overflow-y-auto divide-y divide-slate-800/50">
      <div
        v-for="conv in filteredConversations"
        :key="conv.id"
        @click="chatStore.selectConversation(conv)"
        :class="[
          'p-3 flex items-center gap-3 cursor-pointer transition-all hover:bg-slate-800/40',
          chatStore.activeConversation?.id === conv.id ? 'bg-primary-500/10 border-l-4 border-primary-500' : ''
        ]"
      >
        <!-- Avatar -->
        <Avatar
          :name="getConversationName(conv)"
          :src="conv.avatar_url"
          size="md"
          showStatus
          :isOnline="isOtherUserOnline(conv)"
        />

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-baseline">
            <h3 class="text-sm font-semibold text-slate-200 truncate">
              {{ getConversationName(conv) }}
            </h3>
            <span class="text-[10px] text-slate-500 ml-2 whitespace-nowrap">
              {{ formatTime(conv.latestMessage?.created_at || conv.updated_at) }}
            </span>
          </div>

          <p class="text-xs text-slate-400 truncate mt-0.5">
            <span v-if="conv.latestMessage?.sender_id === authStore.user?.id" class="text-slate-500 font-medium">You: </span>
            {{ conv.latestMessage?.body || 'No messages yet' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '../../stores/chat'
import { useAuthStore } from '../../stores/auth'
import Avatar from '../base/Avatar.vue'

defineEmits(['open-new-chat'])

const chatStore = useChatStore()
const authStore = useAuthStore()
const searchQuery = ref('')

const filteredConversations = computed(() => {
  if (!searchQuery.value.trim()) return chatStore.sortedConversations
  const q = searchQuery.value.toLowerCase()
  return chatStore.sortedConversations.filter(c => 
    getConversationName(c).toLowerCase().includes(q)
  )
})

const getConversationName = (conv) => {
  if (conv.type === 'group' && conv.name) return conv.name
  // Find other participant for direct chat
  const other = conv.participants?.find(p => p.user_id !== authStore.user?.id)
  return other?.user?.name || other?.user?.username || 'Chat'
}

const isOtherUserOnline = (conv) => {
  if (conv.type === 'group') return false
  const other = conv.participants?.find(p => p.user_id !== authStore.user?.id)
  return !!other?.user?.is_online
}

const formatTime = (isoString) => {
  if (!isoString) return ''
  const date = new Date(isoString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
