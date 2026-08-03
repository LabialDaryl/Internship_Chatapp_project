<template>
  <div ref="container" class="flex-1 overflow-y-auto p-6 space-y-4 relative" @click="activeDropdownId = null">
    
    <!-- Floating Toast Notification -->
    <div v-if="chatStore.toastMessage" class="fixed top-20 right-8 z-40 bg-violet-600 text-white px-4 py-2 rounded-xl shadow-xl text-xs font-semibold animate-fade-in flex items-center space-x-2">
      <span>✨ {{ chatStore.toastMessage }}</span>
    </div>

    <!-- Messages Loading -->
    <div v-if="loading" class="flex justify-center p-4">
      <span class="text-xs text-slate-500 animate-pulse">Loading conversation history...</span>
    </div>

    <!-- Empty Thread -->
    <div v-else-if="messages.length === 0" class="h-full flex items-center justify-center text-slate-500 text-sm">
      No messages yet. Send a greeting!
    </div>

    <!-- Message Item -->
    <div
      v-for="msg in messages"
      :key="msg.id"
      :id="`message-${msg.id}`"
      :class="[
        'flex flex-col max-w-[85%] space-y-1 relative group transition-all duration-300',
        isOwn(msg) ? 'ml-auto items-end' : 'mr-auto items-start'
      ]"
    >
      <!-- Sender Name (Group Chat) -->
      <span v-if="!isOwn(msg) && isGroup" class="text-[11px] text-violet-400 font-medium px-1">
        {{ msg.sender?.name || msg.sender?.username || 'User' }}
      </span>

      <!-- Quoted Parent Message (Reply Preview Card) -->
      <div v-if="msg.parent" class="px-3 py-1.5 bg-slate-800/60 border-l-2 border-violet-500 rounded-r-xl text-xs text-slate-300 mb-1 max-w-full truncate">
        <span class="font-semibold text-violet-400">@{{ msg.parent.sender?.name || 'User' }}: </span>
        <span class="italic text-slate-400">{{ msg.parent.body }}</span>
      </div>

      <!-- SIDE-BY-SIDE BUBBLE + 3-DOTS ACTION ROW -->
      <div class="flex items-center gap-2 relative w-full" :class="isOwn(msg) ? 'justify-end' : 'justify-start'">
        
        <!-- 3-Dot Options Dropdown (LEFT side for own messages) -->
        <div v-if="isOwn(msg) && !msg.is_deleted" class="relative">
          <button
            @click.stop="activeDropdownId = activeDropdownId === msg.id ? null : msg.id"
            class="w-7 h-7 rounded-full text-slate-400 hover:text-slate-100 hover:bg-slate-800/80 transition-all flex items-center justify-center text-sm opacity-0 group-hover:opacity-100"
            title="Message options"
          >
            ⋮
          </button>
          
          <!-- Options Dropdown Menu -->
          <div
            v-if="activeDropdownId === msg.id"
            class="absolute top-0 right-8 z-30 w-44 bg-slate-900 border border-slate-700/80 rounded-xl shadow-2xl py-1 text-xs text-slate-200 animate-fade-in"
            @click.stop
          >
            <!-- Quick Emoji Bar -->
            <div class="flex items-center justify-between px-2 py-1 border-b border-slate-800 text-sm">
              <span v-for="e in ['❤️', '😂', '👍', '🔥', '😮']" :key="e" @click="handleReaction(msg, e)" class="cursor-pointer hover:scale-125 transition-transform p-1">{{ e }}</span>
            </div>

            <button @click="triggerReply(msg)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>💬</span> <span>Reply</span>
            </button>
            <button @click="copyText(msg.body)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>📋</span> <span>Copy</span>
            </button>
            <button @click="triggerForward(msg)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>➡️</span> <span>Forward</span>
            </button>
            <button v-if="msg.type === 'text'" @click="startEdit(msg)" class="w-full px-3 py-1.5 text-left hover:bg-amber-500/20 text-amber-300 flex items-center space-x-2">
              <span>✏️</span> <span>Edit</span>
            </button>
            <button @click="triggerDelete(msg)" class="w-full px-3 py-1.5 text-left hover:bg-rose-500/20 text-rose-300 flex items-center space-x-2">
              <span>🗑️</span> <span>Delete</span>
            </button>
          </div>
        </div>

        <!-- MESSAGE BUBBLE / DELETED STATE / INLINE EDIT / AUDIO -->
        <!-- DELETED MESSAGE STATE -->
        <div v-if="msg.is_deleted" class="px-4 py-2.5 rounded-2xl text-xs italic text-slate-400 bg-slate-900/60 border border-slate-800">
          🚫 This message was deleted
        </div>

        <!-- IN-LINE EDITING INPUT MODE -->
        <div v-else-if="editingId === msg.id" class="w-full space-y-2">
          <input
            v-model="editBody"
            type="text"
            class="w-full px-3 py-2 bg-slate-800 border border-violet-500 rounded-xl text-sm text-slate-100 focus:outline-none"
          />
          <div class="flex justify-end space-x-2">
            <button @click="editingId = null" class="px-2.5 py-1 text-xs text-slate-400 hover:text-slate-200">Cancel</button>
            <button @click="saveEdit(msg.id)" class="px-3 py-1 text-xs bg-violet-600 text-white font-semibold rounded-lg">Save</button>
          </div>
        </div>

        <!-- AUDIO VOICE MESSAGE TYPE -->
        <AudioPlayerBubble
          v-else-if="msg.type === 'audio'"
          :src="msg.body"
          :isOwn="isOwn(msg)"
        />

        <!-- SYSTEM CALL LOG MESSAGE TYPE -->
        <div v-if="msg.type === 'system'" class="px-4 py-2 bg-slate-800/80 border border-slate-700/60 rounded-2xl text-xs text-slate-300 flex items-center space-x-2 font-medium my-1 shadow-sm">
          <span>📞</span>
          <span>{{ msg.body }}</span>
        </div>

        <!-- IMAGE MESSAGE TYPE -->
        <div v-else-if="msg.type === 'image'" class="overflow-hidden rounded-2xl border border-slate-700/60 shadow-lg group cursor-pointer relative" @click="openLightbox(msg.body)">
          <img :src="msg.body" alt="Attached Image" class="max-w-sm max-h-64 object-cover rounded-2xl transform transition-transform group-hover:scale-105" />
          <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
            <span class="px-2.5 py-1 bg-slate-900/80 text-slate-200 text-xs rounded-lg backdrop-blur-sm">🔍 Expand</span>
          </div>
        </div>

        <!-- FILE ATTACHMENT MESSAGE TYPE -->
        <div v-else-if="msg.type === 'file'"
          :class="[
            'px-4 py-3 rounded-2xl text-sm flex items-center space-x-3 border shadow-sm',
            isOwn(msg)
              ? 'bg-violet-600/90 text-white border-violet-500/50 rounded-br-none'
              : 'bg-slate-800 text-slate-100 border-slate-700/60 rounded-bl-none'
          ]"
        >
          <div class="w-10 h-10 rounded-xl bg-slate-900/40 flex items-center justify-center text-violet-300 font-bold text-base">
            📁
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold truncate">{{ getFileName(msg.body) }}</p>
            <a :href="msg.body" target="_blank" download class="text-[11px] text-violet-300 underline hover:text-white transition-colors">Download Attachment</a>
          </div>
        </div>

        <!-- STANDARD TEXT MESSAGE TYPE (WITH @MENTION HIGHLIGHTING) -->
        <div v-else
          :class="[
            'px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm break-words',
            isOwn(msg)
              ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white rounded-br-none shadow-violet-600/20'
              : 'bg-slate-800 text-slate-100 rounded-bl-none border border-slate-700/50'
          ]"
        >
          <span v-html="renderMentions(msg.body)"></span>
        </div>

        <!-- 3-Dot Options Dropdown (RIGHT side for incoming messages) -->
        <div v-if="!isOwn(msg) && !msg.is_deleted" class="relative">
          <button
            @click.stop="activeDropdownId = activeDropdownId === msg.id ? null : msg.id"
            class="w-7 h-7 rounded-full text-slate-400 hover:text-slate-100 hover:bg-slate-800/80 transition-all flex items-center justify-center text-sm opacity-0 group-hover:opacity-100"
            title="Message options"
          >
            ⋮
          </button>
          
          <!-- Options Dropdown Menu -->
          <div
            v-if="activeDropdownId === msg.id"
            class="absolute top-0 left-8 z-30 w-44 bg-slate-900 border border-slate-700/80 rounded-xl shadow-2xl py-1 text-xs text-slate-200 animate-fade-in"
            @click.stop
          >
            <!-- Quick Emoji Bar -->
            <div class="flex items-center justify-between px-2 py-1 border-b border-slate-800 text-sm">
              <span v-for="e in ['❤️', '😂', '👍', '🔥', '😮']" :key="e" @click="handleReaction(msg, e)" class="cursor-pointer hover:scale-125 transition-transform p-1">{{ e }}</span>
            </div>

            <button @click="triggerReply(msg)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>💬</span> <span>Reply</span>
            </button>
            <button @click="copyText(msg.body)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>📋</span> <span>Copy</span>
            </button>
            <button @click="triggerForward(msg)" class="w-full px-3 py-1.5 text-left hover:bg-violet-600/20 flex items-center space-x-2">
              <span>➡️</span> <span>Forward</span>
            </button>
            <button v-if="isAdmin" @click="triggerDelete(msg)" class="w-full px-3 py-1.5 text-left hover:bg-rose-500/20 text-rose-300 flex items-center space-x-2">
              <span>🗑️</span> <span>Delete</span>
            </button>
          </div>
        </div>

      </div>

      <!-- EMOJI REACTION PILLS -->
      <div v-if="groupedReactions(msg).length > 0" class="flex flex-wrap gap-1 px-1 mt-1">
        <button
          v-for="r in groupedReactions(msg)"
          :key="r.emoji"
          @click="handleReaction(msg, r.emoji)"
          :class="[
            'px-2 py-0.5 rounded-full text-xs flex items-center space-x-1 border transition-all',
            r.hasUserReacted
              ? 'bg-violet-600/30 border-violet-500/60 text-violet-200 font-bold'
              : 'bg-slate-900/60 border-slate-800 text-slate-300 hover:border-slate-700'
          ]"
        >
          <span>{{ r.emoji }}</span>
          <span class="text-[10px]">{{ r.count }}</span>
        </button>
      </div>

      <!-- Time, Edited & Read Status -->
      <div class="flex items-center gap-1.5 px-1">
        <span v-if="msg.is_edited && !msg.is_deleted" class="text-[10px] text-slate-400 italic">(edited)</span>
        <span class="text-[10px] text-slate-500">
          {{ formatTime(msg.created_at) }}
        </span>
        <span v-if="isOwn(msg)" class="text-[10px] text-violet-400 font-bold">
          ✓✓
        </span>
      </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div v-if="lightboxUrl" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md" @click="lightboxUrl = null">
      <div class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl shadow-2xl border border-slate-800">
        <img :src="lightboxUrl" alt="Enlarged view" class="w-full h-full object-contain max-h-[85vh]" />
        <button class="absolute top-4 right-4 text-white bg-slate-900/80 p-2 rounded-full hover:bg-slate-800" @click.stop="lightboxUrl = null">✕</button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <DeleteMessageModal
      :show="showDeleteModal"
      :message="deleteTargetMsg"
      :isOwn="deleteTargetMsg ? isOwn(deleteTargetMsg) : false"
      :isAdmin="isAdmin"
      @close="showDeleteModal = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chat'
import AudioPlayerBubble from './AudioPlayerBubble.vue'
import DeleteMessageModal from './DeleteMessageModal.vue'

const props = defineProps({
  messages: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  isGroup: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['open-forward'])

const authStore = useAuthStore()
const chatStore = useChatStore()

const container = ref(null)
const lightboxUrl = ref(null)
const activeDropdownId = ref(null)
const editingId = ref(null)
const editBody = ref('')

const showDeleteModal = ref(false)
const deleteTargetMsg = ref(null)

const isOwn = (msg) => {
  return msg.sender_id === authStore.user?.id
}

const isAdmin = computed(() => {
  if (!chatStore.activeConversation || chatStore.activeConversation.type !== 'group') return false
  const p = chatStore.activeConversation.participants?.find(part => part.user_id === authStore.user?.id)
  return p?.role === 'admin'
})

const groupedReactions = (msg) => {
  if (!msg.reactions || !Array.isArray(msg.reactions)) return []
  const map = {}
  msg.reactions.forEach(r => {
    if (!map[r.emoji]) {
      map[r.emoji] = { emoji: r.emoji, count: 0, hasUserReacted: false }
    }
    map[r.emoji].count++
    if (r.user_id === authStore.user?.id || r.user?.id === authStore.user?.id) {
      map[r.emoji].hasUserReacted = true
    }
  })
  return Object.values(map)
}

const handleReaction = async (msg, emoji) => {
  activeDropdownId.value = null
  await chatStore.toggleReaction(msg.id, emoji)
}

const triggerReply = (msg) => {
  chatStore.replyingToMessage = msg
  activeDropdownId.value = null
}

const triggerForward = (msg) => {
  emit('open-forward', msg)
  activeDropdownId.value = null
}

const triggerDelete = (msg) => {
  deleteTargetMsg.value = msg
  showDeleteModal.value = true
  activeDropdownId.value = null
}

const handleDeleteConfirm = async ({ messageId, type }) => {
  if (type === 'everyone') {
    await chatStore.deleteMessage(messageId)
  } else {
    chatStore.messages = chatStore.messages.filter(m => m.id !== messageId)
    chatStore.showToast('Removed from your view')
  }
}

const renderMentions = (text) => {
  if (!text) return ''
  return text.replace(/@([a-zA-Z0-9_-]+)/g, '<span class="px-1.5 py-0.5 rounded-md bg-violet-600/30 border border-violet-500/40 text-violet-200 font-semibold text-xs inline-block">@$1</span>')
}

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(text)
  chatStore.showToast('Copied to clipboard!')
  activeDropdownId.value = null
}

const startEdit = (msg) => {
  editingId.value = msg.id
  editBody.value = msg.body
  activeDropdownId.value = null
}

const saveEdit = async (msgId) => {
  if (!editBody.value.trim()) return
  await chatStore.editMessage(msgId, editBody.value)
  editingId.value = null
}

const openLightbox = (url) => {
  lightboxUrl.value = url
}

const getFileName = (url) => {
  if (!url) return 'Attachment'
  return url.substring(url.lastIndexOf('/') + 1)
}

const formatTime = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = () => {
  nextTick(() => {
    if (container.value) {
      container.value.scrollTop = container.value.scrollHeight
    }
  })
}

watch(() => props.messages.length, scrollToBottom)
</script>
