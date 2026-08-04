<template>
  <div class="h-16 px-6 border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/60 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <!-- Back Button for Mobile -->
      <button
        @click="$emit('back')"
        class="md:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
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
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 leading-tight">
          {{ conversationName }}
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          <span v-if="typingStatus" class="text-violet-600 dark:text-violet-400 font-medium animate-pulse">
            {{ typingStatus }}
          </span>
          <span v-else-if="conversation?.type === 'group'" class="flex items-center gap-1">
            <span>{{ conversation.participants?.length || 0 }} members</span>
            <span v-if="onlineOtherCount > 0" class="text-emerald-500 font-medium ml-1">
              • {{ onlineOtherCount === 1 ? 'Active now' : `${onlineOtherCount} active now` }}
            </span>
          </span>
          <span v-else-if="isOnline" class="text-emerald-500 font-medium">Online</span>
          <span v-else class="text-slate-400">Offline</span>
        </p>
      </div>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center space-x-2">
      <!-- Audio Call Button -->
      <button
        @click="$emit('start-call', 'audio')"
        title="Start Audio Call"
        class="p-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:text-emerald-500 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all text-xs font-semibold"
      >
        📞
      </button>

      <!-- Video Call Button -->
      <button
        @click="$emit('start-call', 'video')"
        title="Start Video Call"
        class="p-2.5 rounded-xl text-slate-600 dark:text-slate-400 hover:text-violet-600 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all text-xs font-semibold"
      >
        📹
      </button>

      <!-- Media Gallery Button -->
      <button
        @click="$emit('open-media-gallery')"
        title="Media Gallery"
        class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-violet-600 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all flex items-center space-x-1.5 text-xs font-semibold"
      >
        <span>📁 Media</span>
      </button>

      <!-- In-Chat Search Trigger -->
      <button
        @click="$emit('open-search')"
        title="Search in chat"
        class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-violet-600 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all flex items-center space-x-1.5 text-xs font-semibold"
      >
        <span>🔍 Search</span>
      </button>

      <!-- Group Details Modal trigger -->
      <button
        v-if="conversation?.type === 'group'"
        @click="$emit('open-group-details')"
        title="View group members & info"
        class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-violet-600 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all flex items-center space-x-1.5 text-xs font-semibold"
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

defineEmits(['back', 'open-group-details', 'open-search', 'open-media-gallery', 'start-call'])

const authStore = useAuthStore()
const chatStore = useChatStore()

const conversationName = computed(() => {
  if (!props.conversation) return ''
  if (props.conversation.type === 'group' && props.conversation.name) return props.conversation.name
  const other = props.conversation.participants?.find(p => p.user_id !== authStore.user?.id)
  return other?.user?.name || (other?.user?.username ? `@${other.user.username}` : 'Chat')
})

const onlineOtherCount = computed(() => {
  if (!props.conversation || props.conversation.type !== 'group') return 0
  const otherParticipants = props.conversation.participants?.filter(p => p.user_id !== authStore.user?.id) || []
  return otherParticipants.filter(p => chatStore.isUserOnline(p.user_id) || !!p.user?.is_online).length
})

const isOnline = computed(() => {
  if (!props.conversation) return false
  if (props.conversation.type === 'group') {
    return onlineOtherCount.value > 0
  }
  const other = props.conversation.participants?.find(p => p.user_id !== authStore.user?.id)
  if (!other) return false
  return chatStore.isUserOnline(other.user_id) || !!other.user?.is_online
})

const typingStatus = computed(() => {
  const users = chatStore.activeTypingUsers
  if (users.length === 0) return ''
  if (users.length === 1) return `${users[0]} is typing...`
  return `${users.join(', ')} are typing...`
})
</script>
