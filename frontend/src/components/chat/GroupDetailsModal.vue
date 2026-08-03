<template>
  <div v-if="show && conversation" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-5">
      
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

      <!-- Members List -->
      <div>
        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Group Members</h4>
        <div class="max-h-60 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
          <div
            v-for="participant in conversation.participants"
            :key="participant.id || participant.user_id"
            class="flex items-center justify-between p-2.5 rounded-xl bg-slate-800/40 border border-slate-800/80 hover:bg-slate-800/70 transition-all"
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
                <p class="text-sm font-medium text-slate-200">
                  {{ participant.user?.name || participant.user?.username }}
                  <span v-if="participant.user_id === currentUserId" class="text-xs text-violet-400 font-normal">(You)</span>
                </p>
                <p class="text-xs text-slate-400">@{{ participant.user?.username }}</p>
              </div>
            </div>

            <!-- Role Badge -->
            <span
              class="px-2 py-0.5 text-[10px] font-semibold rounded-md border"
              :class="participant.role === 'admin' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-slate-800 text-slate-400 border-slate-700/50'"
            >
              {{ participant.role || 'member' }}
            </span>
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
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  show: Boolean,
  conversation: Object,
})

const emit = defineEmits(['close', 'leave'])

const authStore = useAuthStore()
const currentUserId = computed(() => authStore.user?.id)

function handleLeave() {
  emit('leave', props.conversation.id)
  emit('close')
}
</script>
