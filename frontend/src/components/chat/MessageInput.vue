<template>
  <div class="border-t border-slate-800 bg-slate-900/40">
    
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

    <form @submit.prevent="handleSend" class="p-4 flex items-center gap-2.5">
      
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

      <!-- Message Text Field -->
      <input
        type="text"
        v-model="text"
        @input="handleInput"
        placeholder="Type a message..."
        class="input-base rounded-xl py-3 text-sm"
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
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useChatStore } from '../../stores/chat'
import messagesService from '../../services/messages'
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
const chatStore = useChatStore()
let lastTypingTime = 0

const handleInput = () => {
  const now = Date.now()
  if (now - lastTypingTime > 2000) {
    chatStore.sendTypingWhisper()
    lastTypingTime = now
  }
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
}
</script>
