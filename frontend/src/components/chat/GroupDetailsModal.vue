<template>
  <div v-if="show && conversation" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-lg p-6 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl space-y-5">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-white text-lg font-bold shadow-lg shadow-violet-500/20">
            {{ conversation.name?.charAt(0) || 'G' }}
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-100">{{ conversation.name || 'Group Conversation' }}</h3>
            <p class="text-xs text-slate-400">{{ conversation.participants?.length || 0 }} members</p>
          </div>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Admin Add Member Trigger Button & Picker Bar -->
      <div v-if="isAdmin" class="space-y-3">
        <button
          @click="showAddMember = !showAddMember"
          class="w-full py-2 px-4 rounded-xl bg-violet-600/20 hover:bg-violet-600/30 text-violet-300 border border-violet-500/40 text-xs font-bold transition-all flex items-center justify-center space-x-2"
        >
          <span>➕ Add Member to Group</span>
        </button>

        <!-- Search & Add Member Selector -->
        <div v-if="showAddMember" class="p-3 bg-slate-950/80 border border-slate-800 rounded-2xl space-y-3">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search contacts to add..."
            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-xs text-slate-100 focus:outline-none"
          />
          
          <div class="max-h-40 overflow-y-auto space-y-1 custom-scrollbar">
            <div
              v-for="user in availableContacts"
              :key="user.id"
              class="flex items-center justify-between p-2 rounded-xl bg-slate-900/60 hover:bg-slate-800 text-xs"
            >
              <span class="font-medium text-slate-200 truncate">{{ user.name || user.username }}</span>
              <button
                @click="handleAddMember(user.id)"
                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold"
              >
                Add
              </button>
            </div>
            <div v-if="availableContacts.length === 0" class="text-center py-2 text-[11px] text-slate-500">
              No contacts available to add.
            </div>
          </div>
        </div>
      </div>

      <!-- Members List -->
      <div>
        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Group Members</h4>
        <div class="max-h-64 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
          <div
            v-for="participant in conversation.participants"
            :key="participant.id || participant.user_id"
            class="flex items-center justify-between p-3 rounded-2xl bg-slate-800/40 border border-slate-800/80 hover:bg-slate-800/70 transition-all"
          >
            <div class="flex items-center space-x-3">
              <div class="relative">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-violet-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold shadow">
                  {{ participant.user?.name?.charAt(0) || participant.user?.username?.charAt(0) || 'U' }}
                </div>
                <!-- Presence Badge -->
                <span
                  class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-slate-900"
                  :class="participant.user?.is_online ? 'bg-emerald-500' : 'bg-slate-500'"
                ></span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-200">
                  {{ participant.user?.name || participant.user?.username }}
                  <span v-if="participant.user_id === currentUserId" class="text-[10px] text-violet-400 font-normal ml-1">(You)</span>
                </p>
                <p class="text-[10px] text-slate-400">@{{ participant.user?.username }}</p>
              </div>
            </div>

            <!-- Controls (Role Badge & Admin Actions) -->
            <div class="flex items-center space-x-2">
              <!-- Role Badge -->
              <span
                class="px-2 py-0.5 text-[10px] font-semibold rounded-md border"
                :class="participant.role === 'admin' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-slate-800 text-slate-400 border-slate-700/50'"
              >
                {{ participant.role === 'admin' ? '👑 Admin' : 'Member' }}
              </span>

              <!-- Admin Management Actions (Promote / Demote / Kick) -->
              <template v-if="isAdmin && participant.user_id !== currentUserId">
                <!-- Toggle Admin Role -->
                <button
                  @click="handleToggleRole(participant)"
                  class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] rounded-lg border border-slate-700 transition-colors"
                  :title="participant.role === 'admin' ? 'Demote to Member' : 'Promote to Admin'"
                >
                  {{ participant.role === 'admin' ? '🛡️ Demote' : '👑 Admin' }}
                </button>

                <!-- Kick Member -->
                <button
                  @click="handleKickMember(participant.user_id)"
                  class="px-2 py-1 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white text-[10px] rounded-lg border border-rose-500/30 transition-colors"
                  title="Kick from group"
                >
                  ❌ Kick
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Action -->
      <div class="flex items-center justify-between pt-3 border-t border-slate-800/80">
        <button
          @click="handleLeave"
          class="px-4 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl border border-rose-500/20 transition-all"
        >
          Leave Group
        </button>
        <button
          @click="emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-xl transition-all"
        >
          Close
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useChatStore } from '../../stores/chat'
import conversationsService from '../../services/conversations'

const props = defineProps({
  show: Boolean,
  conversation: Object,
})

const emit = defineEmits(['close', 'leave'])

const authStore = useAuthStore()
const chatStore = useChatStore()

const showAddMember = ref(false)
const searchQuery = ref('')

const currentUserId = computed(() => authStore.user?.id)

const isAdmin = computed(() => {
  if (!props.conversation || props.conversation.type !== 'group') return false
  const p = props.conversation.participants?.find(part => part.user_id === currentUserId.value)
  return p?.role === 'admin'
})

const availableContacts = computed(() => {
  const existingIds = new Set((props.conversation?.participants || []).map(p => p.user_id))
  return (chatStore.searchResults || [])
    .filter(u => !existingIds.has(u.id))
    .filter(u => {
      if (!searchQuery.value.trim()) return true
      const q = searchQuery.value.toLowerCase()
      return (u.name && u.name.toLowerCase().includes(q)) || (u.username && u.username.toLowerCase().includes(q))
    })
})

const handleAddMember = async (userId) => {
  try {
    const res = await conversationsService.addParticipant(props.conversation.id, userId)
    if (res.conversation) {
      props.conversation.participants = res.conversation.participants
    }
    if (res.system_message) {
      chatStore.handleIncomingMessage(res.system_message)
    }
    chatStore.showToast('Member added!')
  } catch {
    chatStore.showToast('Failed to add member')
  }
}

const handleKickMember = async (userId) => {
  try {
    const res = await conversationsService.removeParticipant(props.conversation.id, userId)
    if (res.conversation) {
      props.conversation.participants = res.conversation.participants
    }
    if (res.system_message) {
      chatStore.handleIncomingMessage(res.system_message)
    }
    chatStore.showToast('Member removed!')
  } catch {
    chatStore.showToast('Failed to remove member')
  }
}

const handleToggleRole = async (participant) => {
  const newRole = participant.role === 'admin' ? 'member' : 'admin'
  try {
    const res = await conversationsService.updateParticipantRole(props.conversation.id, participant.user_id, newRole)
    if (res.conversation) {
      props.conversation.participants = res.conversation.participants
    }
    if (res.system_message) {
      chatStore.handleIncomingMessage(res.system_message)
    }
    chatStore.showToast(`Updated role to ${newRole}`)
  } catch {
    chatStore.showToast('Failed to update role')
  }
}

function handleLeave() {
  emit('leave', props.conversation.id)
  emit('close')
}
</script>
