<template>
  <div ref="container" class="flex-1 overflow-y-auto p-6 space-y-4 relative">
    
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
      :class="[
        'flex flex-col max-w-[75%] space-y-1 relative group',
        isOwn(msg) ? 'ml-auto items-end' : 'mr-auto items-start'
      ]"
    >
      <!-- Hover Action Toolbar -->
      <div
        v-if="!msg.is_deleted"
        :class="[
          'absolute top-0 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center bg-slate-900/90 border border-slate-700/80 rounded-xl px-2 py-1 space-x-1 shadow-lg backdrop-blur-sm',
          isOwn(msg) ? '-left-36' : '-right-36'
        ]"
      >
        <button @click="chatStore.replyingToMessage = msg" title="Reply to message" class="p-1 hover:text-violet-400 text-slate-400 text-xs">💬</button>
        <button @click="copyText(msg.body)" title="Copy text" class="p-1 hover:text-violet-400 text-slate-400 text-xs">📋</button>
        <button @click="$emit('open-forward', msg)" title="Forward message" class="p-1 hover:text-violet-400 text-slate-400 text-xs">➡️</button>
        <button v-if="isOwn(msg) && msg.type === 'text'" @click="startEdit(msg)" title="Edit message" class="p-1 hover:text-amber-400 text-slate-400 text-xs">✏️</button>
        <button v-if="isOwn(msg) || isAdmin" @click="chatStore.deleteMessage(msg.id)" title="Delete message" class="p-1 hover:text-rose-400 text-slate-400 text-xs">🗑️</button>
      </div>

      <!-- Sender Name (Group Chat) -->
      <span v-if="!isOwn(msg) && isGroup" class="text-[11px] text-violet-400 font-medium px-1">
        {{ msg.sender?.name || msg.sender?.username || 'User' }}
      </span>

      <!-- Quoted Parent Message (Reply Preview Card) -->
      <div v-if="msg.parent" class="px-3 py-1.5 bg-slate-800/60 border-l-2 border-violet-500 rounded-r-xl text-xs text-slate-300 mb-1 max-w-full truncate">
        <span class="font-semibold text-violet-400">@{{ msg.parent.sender?.name || 'User' }}: </span>
        <span class="italic text-slate-400">{{ msg.parent.body }}</span>
      </div>

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

      <!-- STANDARD TEXT MESSAGE TYPE -->
      <div v-else
        :class="[
          'px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm break-words',
          isOwn(msg)
            ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white rounded-br-none shadow-violet-600/20'
            : 'bg-slate-800 text-slate-100 rounded-bl-none border border-slate-700/50'
        ]"
      >
        {{ msg.body }}
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
  </div>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chat'

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

defineEmits(['open-forward'])

const authStore = useAuthStore()
const chatStore = useChatStore()

const container = ref(null)
const lightboxUrl = ref(null)
const editingId = ref(null)
const editBody = ref('')

const isOwn = (msg) => {
  return msg.sender_id === authStore.user?.id
}

const isAdmin = computed(() => {
  if (!chatStore.activeConversation || chatStore.activeConversation.type !== 'group') return false
  const p = chatStore.activeConversation.participants?.find(part => part.user_id === authStore.user?.id)
  return p?.role === 'admin'
})

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(text)
  chatStore.showToast('Copied to clipboard!')
}

const startEdit = (msg) => {
  editingId.value = msg.id
  editBody.value = msg.body
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
