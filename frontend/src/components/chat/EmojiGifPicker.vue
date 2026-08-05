<template>
  <div v-if="show" class="absolute bottom-16 left-4 z-40 w-80 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden animate-fade-in">
    
    <!-- Tab Navigation Bar (Emojis vs GIFs) -->
    <div class="flex items-center border-b border-slate-800 bg-slate-950/60 p-2">
      <button
        @click="activeTab = 'emoji'"
        :class="[
          'flex-1 py-1.5 text-xs font-bold rounded-xl transition-all',
          activeTab === 'emoji' ? 'bg-violet-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        😊 Emojis
      </button>
      <button
        @click="activeTab = 'gif'"
        :class="[
          'flex-1 py-1.5 text-xs font-bold rounded-xl transition-all',
          activeTab === 'gif' ? 'bg-violet-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        🎞️ GIFs
      </button>
    </div>

    <!-- EMOJI TAB CONTENT -->
    <div v-if="activeTab === 'emoji'" class="p-3 space-y-3">
      <!-- Category Selector -->
      <div class="flex space-x-2 overflow-x-auto pb-1 custom-scrollbar text-sm">
        <button
          v-for="(cat, key) in emojiCategories"
          :key="key"
          @click="selectedCategory = key"
          :class="['px-2 py-1 rounded-lg transition-colors', selectedCategory === key ? 'bg-slate-800 text-violet-400 font-bold' : 'text-slate-400 hover:bg-slate-800/50']"
        >
          {{ cat.icon }}
        </button>
      </div>

      <!-- Emoji Grid -->
      <div class="grid grid-cols-6 gap-1 max-h-56 overflow-y-auto custom-scrollbar p-1">
        <button
          v-for="e in emojiCategories[selectedCategory].list"
          :key="e"
          @click="emit('select-emoji', e)"
          class="h-9 rounded-xl hover:bg-slate-800 flex items-center justify-center text-xl transition-transform hover:scale-125"
        >
          {{ e }}
        </button>
      </div>
    </div>

    <!-- GIF TAB CONTENT -->
    <div v-else-if="activeTab === 'gif'" class="p-3 space-y-3">
      <!-- Search Input -->
      <input
        v-model="gifQuery"
        @input="handleGifSearch"
        type="text"
        placeholder="Search GIFs..."
        class="w-full px-3 py-2 bg-slate-800 border border-slate-700/80 rounded-xl text-xs text-slate-100 focus:outline-none focus:border-violet-500"
      />

      <!-- GIF List Grid -->
      <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto custom-scrollbar p-1">
        <div
          v-for="gif in filteredGifs"
          :key="gif.id"
          @click="emit('select-gif', gif.url)"
          class="h-24 rounded-xl overflow-hidden cursor-pointer border border-slate-800 hover:border-violet-500/80 transition-all transform hover:scale-105 group relative bg-slate-950"
        >
          <img :src="gif.url" :alt="gif.title" @error="handleGifError(gif)" class="w-full h-full object-cover" />
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

defineProps({
  show: Boolean
})

const emit = defineEmits(['select-emoji', 'select-gif'])

const activeTab = ref('emoji')
const selectedCategory = ref('smileys')
const gifQuery = ref('')

const emojiCategories = {
  smileys: {
    icon: '😃',
    list: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕']
  },
  gestures: {
    icon: '👋',
    list: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪']
  },
  hearts: {
    icon: '❤️',
    list: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '🔥', '✨', '🌟', '💫', '💥', '💯']
  },
  animals: {
    icon: '🐶',
    list: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🐦', '🐤', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜', '🦟', '🦗', '🕷️', '🦂', '🐢', '🐍', '🦎', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪', '🐫', '🦒', '🦘', '🐂', '🐃', '🐄', '🐎', '🐖', '🐏', '🐑', 'llama', '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐓', '🦃', '🦚', '🦜', '🦩', '🕊️', '🐇', '🦝', '🦨', '🦡', '🦦', '🦥', '🦔']
  }
}

const sampleGifs = ref([
  { id: 1, title: 'Happy Dance', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExbnZ5bmxybnJqYnIydnBqMGtyMnZpOG1scWkybjRraWNsbHFyZmFqZSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/l0AM41dsqFz0lM4iI/giphy.gif' },
  { id: 2, title: 'Mind Blown', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExOHlybnZocmZ4b281d2wzdm5scXcyOGoxNHN0dm10ZnlycDFsd2R0eCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/26ufdipQqU2lhNA4g/giphy.gif' },
  { id: 3, title: 'Cat Vibe', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExOWd0ZWNmZXJpd2tsYmJ5cnMxeWJqbjdrMG5ubXRmZHlhdmM1dHZrZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/CjmvTCZfKLWK91w8mu/giphy.gif' },
  { id: 4, title: 'Thumbs Up', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExYnFkZDJyZzFvYWZ5NWV1MnU1MWFzMW1iNTR0ZjBxdDFudThtanRwZSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/111ebonMs90YLu/giphy.gif' },
  { id: 5, title: 'Laughing', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExMHgyNDgzdnlyZms0dWVzNW9xYXhmdXZvZGF2eTliam1lczZ6cDFtZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/M8x6Lk2QFmTu0/giphy.gif' },
  { id: 6, title: 'Party Time', url: 'https://i.giphy.com/media/v1.Y2lkPTc5MGI3NjExMXl2b3JycXh0a2lqM3BveTJmZm5ncm14eXR4NmZzNWp0dHFid3RhYSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/g9582DNuQppxC/giphy.gif' }
])

const filteredGifs = computed(() => {
  if (!gifQuery.value.trim()) return sampleGifs
  return sampleGifs.filter(g => g.title.toLowerCase().includes(gifQuery.value.toLowerCase()))
})

function handleGifSearch() {
  // Filters reactively
}
</script>
