<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-md p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-violet-600/10 text-violet-600 dark:text-violet-400 flex items-center justify-center text-lg font-bold">
            ✏️
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Edit Nicknames</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Set custom nicknames visible in this chat</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Feedback Alert -->
      <div v-if="statusMsg" class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-600 dark:text-emerald-300 font-medium">
        {{ statusMsg }}
      </div>

      <!-- Members Nickname Editing List -->
      <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
        <div
          v-for="p in participants"
          :key="p.id"
          class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-2"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2.5 min-w-0">
              <Avatar :name="p.user?.name" :src="p.user?.avatar_url" size="sm" />
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ p.user?.name }}</p>
                <p class="text-[10px] text-slate-400 truncate">@{{ p.user?.username }}</p>
              </div>
            </div>
            <span v-if="p.pivot?.nickname || p.nickname" class="text-[10px] font-semibold text-violet-600 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-md">
              Nickname Set
            </span>
          </div>

          <!-- Edit Nickname Input Row -->
          <div class="flex items-center space-x-2">
            <input
              type="text"
              v-model="nicknameInputs[p.user_id || p.user?.id]"
              placeholder="Set nickname..."
              maxlength="100"
              class="flex-1 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500"
            />
            <button
              @click="saveNickname(p.user_id || p.user?.id)"
              :disabled="savingUserId === (p.user_id || p.user?.id)"
              class="px-3 py-1.5 text-xs font-bold text-white bg-violet-600 hover:bg-violet-500 rounded-xl transition-all disabled:opacity-50"
            >
              {{ savingUserId === (p.user_id || p.user?.id) ? '...' : 'Save' }}
            </button>
            <button
              v-if="nicknameInputs[p.user_id || p.user?.id]"
              @click="clearNickname(p.user_id || p.user?.id)"
              title="Clear nickname"
              class="px-2 py-1.5 text-xs font-semibold text-slate-400 hover:text-rose-500 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-xl transition-all"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
        <button
          @click="emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all"
        >
          Done
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import conversationsService from '../../services/conversations'
import { useChatStore } from '../../stores/chat'
import Avatar from '../base/Avatar.vue'

const props = defineProps({
  show: Boolean,
  conversation: Object
})

const emit = defineEmits(['close'])
const chatStore = useChatStore()

const nicknameInputs = reactive({})
const savingUserId = ref(null)
const statusMsg = ref('')

const participants = computed(() => props.conversation?.participants || [])

watch(() => props.show, (newVal) => {
  if (newVal && props.conversation) {
    statusMsg.value = ''
    participants.value.forEach(p => {
      const uId = p.user_id || p.user?.id
      const currentNick = p.pivot?.nickname || p.nickname || ''
      nicknameInputs[uId] = currentNick
    })
  }
}, { immediate: true })

async function saveNickname(userId) {
  if (!props.conversation?.id || !userId) return
  savingUserId.value = userId
  statusMsg.value = ''
  try {
    const nick = nicknameInputs[userId] || ''
    const res = await conversationsService.updateParticipantNickname(props.conversation.id, userId, nick)
    if (res.conversation) {
      chatStore.updateActiveConversationData(res.conversation)
    }
    if (res.system_message) {
      chatStore.appendIncomingMessage(res.system_message)
    }
    statusMsg.value = nick ? 'Nickname updated!' : 'Nickname cleared!'
  } catch (e) {
    statusMsg.value = 'Failed to update nickname.'
  } finally {
    savingUserId.value = null
  }
}

async function clearNickname(userId) {
  nicknameInputs[userId] = ''
  await saveNickname(userId)
}
</script>
