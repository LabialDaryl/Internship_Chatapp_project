<template>
  <div class="h-16 px-6 border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/60 flex items-center justify-between z-20">
    <div class="flex items-center gap-3 min-w-0">
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

      <div class="min-w-0 flex-1">
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 leading-tight truncate">
          {{ conversationName }}
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
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

    <!-- Header Quick Actions & 3-Dot Options Menu -->
    <div class="flex items-center space-x-2 relative">
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

      <!-- 3-Dot Options Menu Button -->
      <div class="relative">
        <button
          @click="showMenu = !showMenu"
          title="Conversation options"
          class="w-10 h-10 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 transition-all flex items-center justify-center text-lg font-bold"
        >
          ⋮
        </button>

        <!-- 3-Dot Options Popover Dropdown -->
        <div
          v-if="showMenu"
          class="absolute right-0 top-12 z-50 w-56 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl space-y-1 animate-fade-in"
          @click.stop
        >
          <!-- Search in Conversation -->
          <button
            @click="showMenu = false; $emit('open-search')"
            class="w-full px-3 py-2.5 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5 text-xs font-semibold transition-colors"
          >
            <span>🔍</span>
            <span>Search in Chat</span>
          </button>

          <!-- Shared Media & Files -->
          <button
            @click="showMenu = false; $emit('open-media-gallery')"
            class="w-full px-3 py-2.5 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5 text-xs font-semibold transition-colors"
          >
            <span>📁</span>
            <span>Media & Attachments</span>
          </button>

          <!-- Edit Nicknames -->
          <button
            @click="showMenu = false; $emit('open-nicknames')"
            class="w-full px-3 py-2.5 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5 text-xs font-semibold transition-colors"
          >
            <span>✏️</span>
            <span>Edit Nicknames</span>
          </button>

          <!-- Group Details & Members (For Group Chat) -->
          <button
            v-if="conversation?.type === 'group'"
            @click="showMenu = false; $emit('open-group-details')"
            class="w-full px-3 py-2.5 rounded-xl text-left hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5 text-xs font-semibold transition-colors border-t border-slate-100 dark:border-slate-800 pt-2"
          >
            <span>👥</span>
            <span>Group Info & Members</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chat'
import Avatar from '../base/Avatar.vue'

const props = defineProps({
  conversation: {
    type: Object,
    default: null
  }
})

defineEmits(['back', 'open-group-details', 'open-search', 'open-media-gallery', 'start-call', 'open-nicknames'])

const authStore = useAuthStore()
const chatStore = useChatStore()
const showMenu = ref(false)

const conversationName = computed(() => {
  if (!props.conversation) return ''
  if (props.conversation.type === 'group' && props.conversation.name) return props.conversation.name
  const other = props.conversation.participants?.find(p => p.user_id !== authStore.user?.id)
  return other?.pivot?.nickname || other?.nickname || other?.user?.name || (other?.user?.username ? `@${other.user.username}` : 'Chat')
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
