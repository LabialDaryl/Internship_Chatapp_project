<template>
  <div v-if="show && message" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-violet-600/20 text-violet-400 flex items-center justify-center text-lg font-bold border border-violet-500/20">
            ➡️
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-100">Forward Message</h3>
            <p class="text-xs text-slate-400">Select a conversation to forward to</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Message Preview Box -->
      <div class="p-3 bg-slate-800/60 border border-slate-700/60 rounded-xl text-xs text-slate-300 italic">
        <span class="font-semibold not-italic text-violet-300">"</span>
        {{ message.type === 'image' ? '[Image Attachment]' : (message.body || 'Message') }}
        <span class="font-semibold not-italic text-violet-300">"</span>
      </div>

      <!-- Target Conversations List -->
      <div class="max-h-60 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          @click="handleForward(conv.id)"
          class="flex items-center justify-between p-3 rounded-xl bg-slate-800/30 border border-slate-800 hover:bg-violet-600/15 hover:border-violet-500/40 cursor-pointer transition-all group"
        >
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-white text-xs font-bold shadow">
              {{ getConvName(conv).charAt(0) }}
            </div>
            <div>
              <p class="text-sm font-medium text-slate-200 group-hover:text-violet-200 transition-colors">{{ getConvName(conv) }}</p>
              <p class="text-xs text-slate-400">{{ conv.type === 'group' ? 'Group Chat' : 'Direct Message' }}</p>
            </div>
          </div>
          <span class="text-xs text-violet-400 font-semibold group-hover:translate-x-1 transition-transform">Send →</span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  show: Boolean,
  message: Object,
  conversations: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'forward'])

const authStore = useAuthStore()

function getConvName(conv) {
  if (conv.type === 'group' && conv.name) return conv.name
  const other = conv.participants?.find(p => p.user_id !== authStore.user?.id)
  return other?.user?.name || (other?.user?.username ? `@${other.user.username}` : 'Chat')
}

function handleForward(targetConversationId) {
  emit('forward', { messageId: props.message.id, targetConversationId })
  emit('close')
}
</script>
