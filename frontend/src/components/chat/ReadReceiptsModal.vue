<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-md p-6 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl space-y-4">
      
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div class="flex items-center space-x-2 text-violet-400">
          <span class="text-lg">✓✓</span>
          <h3 class="text-base font-bold text-slate-100">Message Read Receipts</h3>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-200">✕</button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="py-8 text-center text-xs text-slate-400 animate-pulse">
        Loading read timestamps...
      </div>

      <!-- Receipts List -->
      <div v-else class="max-h-64 overflow-y-auto space-y-3 custom-scrollbar pr-1">
        <div v-if="receipts.length === 0" class="text-center py-6 text-xs text-slate-500">
          No participants have read this message yet.
        </div>

        <div
          v-for="r in receipts"
          :key="r.id"
          class="flex items-center justify-between p-3 bg-slate-800/50 border border-slate-800 rounded-2xl"
        >
          <div class="flex items-center space-x-3">
            <Avatar :name="r.user?.name" :src="r.user?.avatar_url" size="sm" />
            <div>
              <p class="text-xs font-bold text-slate-200">{{ r.user?.name || 'User' }}</p>
              <p class="text-[10px] text-slate-400">@{{ r.user?.username }}</p>
            </div>
          </div>
          <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-lg">
            {{ formatTime(r.read_at) }}
          </span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Avatar from '../base/Avatar.vue'
import messagesService from '../../services/messages'

const props = defineProps({
  show: Boolean,
  messageId: Number
})

const emit = defineEmits(['close'])

const receipts = ref([])
const loading = ref(false)

watch(() => props.show, async (val) => {
  if (val && props.messageId) {
    loading.value = true
    try {
      receipts.value = await messagesService.getMessageReadReceipts(props.messageId)
    } catch {
      receipts.value = []
    } finally {
      loading.value = false
    }
  }
})

function formatTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
