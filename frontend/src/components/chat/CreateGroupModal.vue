<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl space-y-5 transform transition-all scale-100">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-violet-600/20 text-violet-400 flex items-center justify-center font-semibold text-lg border border-violet-500/20">
            👥
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-100">Create Group Chat</h3>
            <p class="text-xs text-slate-400">Bring your team together</p>
          </div>
        </div>
        <button @click="close" class="text-slate-400 hover:text-slate-200 transition-colors">
          ✕
        </button>
      </div>

      <!-- Group Name Input -->
      <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Group Name</label>
        <input
          v-model="groupName"
          type="text"
          placeholder="e.g. Frontend Guild"
          class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-slate-200 text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-all placeholder:text-slate-500"
        />
      </div>

      <!-- Contact Search & Multi-select -->
      <div>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Members ({{ selectedIds.length }})</label>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search contacts..."
          class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700/50 rounded-xl text-slate-200 text-xs focus:outline-none focus:border-violet-500 transition-all placeholder:text-slate-500 mb-3"
        />

        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1 custom-scrollbar">
          <div
            v-for="contact in filteredContacts"
            :key="contact.id"
            @click="toggleSelect(contact.id)"
            class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition-all border"
            :class="selectedIds.includes(contact.id) ? 'bg-violet-600/15 border-violet-500/40 text-violet-200' : 'bg-slate-800/30 border-transparent hover:bg-slate-800/60 text-slate-300'"
          >
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-white text-xs font-bold shadow">
                {{ contact.name?.charAt(0) || contact.username?.charAt(0) }}
              </div>
              <div>
                <p class="text-sm font-medium">{{ contact.name || contact.username }}</p>
                <p class="text-xs text-slate-400">@{{ contact.username }}</p>
              </div>
            </div>
            <div class="w-5 h-5 rounded-md border flex items-center justify-center text-xs transition-colors"
                 :class="selectedIds.includes(contact.id) ? 'bg-violet-600 border-violet-500 text-white' : 'border-slate-600 bg-slate-800/50'">
              <span v-if="selectedIds.includes(contact.id)">✓</span>
            </div>
          </div>

          <p v-if="filteredContacts.length === 0" class="text-xs text-center text-slate-500 py-4">
            No contacts found
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800/80">
        <button
          @click="close"
          class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-xl transition-all"
        >
          Cancel
        </button>
        <button
          @click="handleCreate"
          :disabled="!groupName.trim() || selectedIds.length === 0 || loading"
          class="px-5 py-2 text-xs font-semibold text-white bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl shadow-lg shadow-violet-600/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        >
          {{ loading ? 'Creating...' : 'Create Group' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '../../stores/chat'

const props = defineProps({
  show: Boolean,
  contacts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close'])

const chatStore = useChatStore()
const groupName = ref('')
const searchQuery = ref('')
const selectedIds = ref([])
const loading = ref(false)

const filteredContacts = computed(() => {
  if (!searchQuery.value.trim()) return props.contacts
  const q = searchQuery.value.toLowerCase()
  return props.contacts.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) || 
    (c.username && c.username.toLowerCase().includes(q))
  )
})

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(idx, 1)
  }
}

function close() {
  groupName.value = ''
  selectedIds.value = []
  searchQuery.value = ''
  emit('close')
}

async function handleCreate() {
  if (!groupName.value.trim() || selectedIds.value.length === 0) return

  loading.value = true
  try {
    await chatStore.createGroupChat(groupName.value.trim(), selectedIds.value)
    close()
  } catch (e) {
    // Error handled by store
  } finally {
    loading.value = false
  }
}
</script>
