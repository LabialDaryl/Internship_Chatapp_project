<template>
  <div class="h-16 px-6 border-b border-slate-800 bg-slate-900/60 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <!-- Back Button for Mobile -->
      <button
        @click="$emit('back')"
        class="md:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-800"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>

      <!-- Active Chat Avatar & Info -->
      <Avatar
        :name="conversationName"
        :src="conversation?.avatar_url"
        size="md"
        showStatus
        :isOnline="isOnline"
      />

      <div>
        <h2 class="text-base font-bold text-slate-100 leading-tight">
          {{ conversationName }}
        </h2>
        <p class="text-xs text-slate-400">
          <span v-if="typingStatus" class="text-violet-400 font-medium animate-pulse">
            {{ typingStatus }}
          </span>
          <span v-else-if="conversation?.type === 'group'">{{ conversation.participants?.length || 0 }} members</span>
          <span v-else-if="isOnline" class="text-emerald-400 font-medium">Online</span>
          <span v-else class="text-slate-500">Offline</span>
        </p>
      </div>
    </div>

    <!-- Header Actions (Group Details Modal trigger) -->
    <div v-if="conversation?.type === 'group'" class="flex items-center space-x-2">
      <button
        @click="$emit('open-group-details')"
        title="View group members & info"
        class="p-2 rounded-xl text-slate-400 hover:text-violet-300 hover:bg-slate-800/80 border border-slate-800/80 transition-all flex items-center space-x-1.5 text-xs font-semibold"
      >
        <span>👥 Info</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chat'
import Avatar from '../base/Avatar.vue'

const props = defineProps({
  conversation: {
    type: Object,
    default: null
  }
})

defineEmits(['back', 'open-group-details'])

const authStore = useAuthStore()
const chatStore = useChatStore()

const conversationName = computed(() => {
  if (!props.conversation) return ''
  if (props.conversation.type === 'group' && props.conversation.name) return props.conversation.name
  const other = props.conversation.participants?.find(p => p.user_id !== authStore.user?.id)
  return other?.user?.name || (other?.user?.username ? `@${other.user.username}` : 'Chat')
})

const isOnline = computed(() => {
  if (!props.conversation || props.conversation.type === 'group') return false
  const other = props.conversation.participants?.find(p => p.user_id !== authStore.user?.id)
  return !!other?.user?.is_online
})

const typingStatus = computed(() => {
  const users = chatStore.activeTypingUsers
  if (users.length === 0) return ''
  if (users.length === 1) return `${users[0]} is typing...`
  return `${users.join(', ')} are typing...`
})
</script>
