<template>
  <div class="border-t border-slate-800 bg-slate-900/40 relative">
    
    <!-- Voice Recording Mode Bar -->
    <VoiceMessageRecorder
      v-if="chatStore.isRecordingVoice"
      @send="handleVoiceSend"
      @cancel="chatStore.isRecordingVoice = false"
    />

    <!-- Standard Text Input Bar -->
    <template v-else>
      <!-- Emoji & GIF Picker Popover -->
      <EmojiGifPicker
        :show="showEmojiPicker"
        @select-emoji="handleSelectEmoji"
        @select-gif="handleSelectGif"
      />

      <!-- @Mention Autocomplete Popup -->
      <div
        v-if="showMentionPopup && mentionCandidates.length > 0"
        class="absolute bottom-full left-4 mb-2 w-64 bg-slate-900 border border-slate-700/80 rounded-xl shadow-2xl overflow-hidden z-30 animate-fade-in"
      >
        <div class="px-3 py-1.5 bg-slate-800/80 text-[10px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-700/60">
          Mention Member
        </div>
        <div class="max-h-40 overflow-y-auto custom-scrollbar">
          <div
            v-for="user in mentionCandidates"
            :key="user.id"
            @click="insertMention(user.username || user.name)"
            class="px-3 py-2 flex items-center space-x-2.5 hover:bg-violet-600/20 cursor-pointer transition-colors border-b border-slate-800/50 last:border-0"
          >
            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-[10px] font-bold text-white">
              {{ user.name?.charAt(0) || user.username?.charAt(0) }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-slate-200 truncate">{{ user.name || user.username }}</p>
              <p class="text-[10px] text-slate-400">@{{ user.username }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Replying Preview Top Bar -->
      <div v-if="chatStore.replyingToMessage" class="px-4 py-2 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between animate-fade-in text-xs">
        <div class="flex items-center space-x-2 min-w-0">
          <span class="text-violet-400 font-bold">💬 Replying to {{ chatStore.replyingToMessage.sender?.name || 'Message' }}:</span>
          <span class="text-slate-300 truncate max-w-md italic">"{{ chatStore.replyingToMessage.body }}"</span>
        </div>
        <button @click="chatStore.replyingToMessage = null" class="text-slate-400 hover:text-slate-200 transition-colors ml-2">
          ✕
        </button>
      </div>

      <form @submit.prevent="handleSend" class="p-4 flex items-center gap-2">
        
        <!-- Attachment Paperclip Button -->
        <button
          type="button"
          @click="triggerFileSelect"
          :disabled="disabled || uploading"
          title="Attach image or file"
          class="p-2.5 rounded-xl text-slate-400 hover:text-violet-300 hover:bg-slate-800/80 border border-transparent hover:border-slate-700/60 transition-all disabled:opacity-50"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
          </svg>
        </button>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          @change="handleFileChange"
          accept="image/*,.pdf,.doc,.docx,.zip,.txt"
        />

        <!-- Emoji & GIF Picker Button -->
        <button
          type="button"
          @click="showEmojiPicker = !showEmojiPicker"
          :disabled="disabled || uploading"
          title="Emoji & GIF Picker"
          :class="[
            'p-2.5 rounded-xl border border-transparent transition-all disabled:opacity-50',
            showEmojiPicker ? 'bg-violet-600/30 text-violet-300 border-violet-500/50' : 'text-slate-400 hover:text-violet-300 hover:bg-slate-800/80 hover:border-slate-700/60'
          ]"
        >
          😊
        </button>

        <!-- Microphone Button -->
        <button
          type="button"
          @click="chatStore.isRecordingVoice = true"
          :disabled="disabled || uploading"
          title="Record voice note"
          class="p-2.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-slate-800/80 border border-transparent hover:border-slate-700/60 transition-all disabled:opacity-50"
        >
          🎙️
        </button>

        <!-- Message Text Field -->
        <input
          type="text"
          v-model="text"
          @input="handleInput"
          placeholder="Type a message... (Type @ to mention)"
          class="input-base rounded-xl py-3 text-sm flex-1"
          :disabled="disabled || uploading"
        />

        <!-- Send Button -->
        <Button
          type="submit"
          variant="primary"
          class="rounded-xl px-4 py-3 shadow-lg shadow-violet-600/20"
          :disabled="!text.trim() || disabled || uploading"
        >
          <svg v-if="!uploading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7-9 7 9 7zm0 0v-8"></path>
          </svg>
          <span v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
        </Button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '../../stores/chat'
import messagesService from '../../services/messages'
import VoiceMessageRecorder from './VoiceMessageRecorder.vue'
import EmojiGifPicker from './EmojiGifPicker.vue'
import Button from '../base/Button.vue'

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['send'])
const text = ref('')
const fileInput = ref(null)
const uploading = ref(false)
const showMentionPopup = ref(false)
const showEmojiPicker = ref(false)
const mentionQuery = ref('')
const chatStore = useChatStore()
let lastTypingTime = 0

const participants = computed(() => {
  if (!chatStore.activeConversation) return []
  return (chatStore.activeConversation.participants || []).map(p => p.user).filter(Boolean)
})

const mentionCandidates = computed(() => {
  if (!mentionQuery.value) return participants.value
  const q = mentionQuery.value.toLowerCase()
  return participants.value.filter(u => 
    (u.name && u.name.toLowerCase().includes(q)) || 
    (u.username && u.username.toLowerCase().includes(q))
  )
})

const handleSelectEmoji = (emoji) => {
  text.value += emoji
}

const handleSelectGif = async (gifUrl) => {
  showEmojiPicker.value = false
  if (!chatStore.activeConversation) return
  await chatStore.sendMessage(gifUrl, 'image')
}

const handleVoiceSend = async (audioBlob) => {
  await chatStore.sendVoiceNote(audioBlob)
}

const handleInput = (e) => {
  const val = text.value
  const lastAt = val.lastIndexOf('@')
  
  if (lastAt !== -1 && (lastAt === 0 || val.charAt(lastAt - 1) === ' ')) {
    const afterAt = val.substring(lastAt + 1)
    if (!afterAt.includes(' ')) {
      showMentionPopup.value = true
      mentionQuery.value = afterAt
    } else {
      showMentionPopup.value = false
    }
  } else {
    showMentionPopup.value = false
  }

  const now = Date.now()
  if (now - lastTypingTime > 2000) {
    chatStore.sendTypingWhisper()
    lastTypingTime = now
  }
}

const insertMention = (username) => {
  const lastAt = text.value.lastIndexOf('@')
  if (lastAt !== -1) {
    text.value = text.value.substring(0, lastAt) + `@${username} `
  }
  showMentionPopup.value = false
}

const triggerFileSelect = () => {
  fileInput.value?.click()
}

const handleFileChange = async (e) => {
  const files = e.target.files
  if (!files || files.length === 0) return

  const file = files[0]
  if (!chatStore.activeConversation) return

  uploading.value = true
  try {
    const parentId = chatStore.replyingToMessage?.id || null
    const message = await messagesService.sendAttachment(chatStore.activeConversation.id, file, parentId)
    chatStore.handleIncomingMessage(message)
    chatStore.replyingToMessage = null
  } catch (err) {
    chatStore.error = err.response?.data?.message || 'Failed to upload attachment'
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const handleSend = () => {
  if (!text.value.trim()) return
  emit('send', text.value)
  text.value = ''
  showMentionPopup.value = false
  showEmojiPicker.value = false
}
</script>
