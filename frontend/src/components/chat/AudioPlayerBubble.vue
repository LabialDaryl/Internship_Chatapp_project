<template>
  <div
    :class="[
      'px-4 py-3 rounded-2xl text-sm flex items-center space-x-3 border shadow-sm min-w-[220px]',
      isOwn
        ? 'bg-violet-600/90 text-white border-violet-500/50 rounded-br-none'
        : 'bg-slate-800 text-slate-100 border-slate-700/60 rounded-bl-none'
    ]"
  >
    <audio ref="audioRef" :src="src" @timeupdate="onTimeUpdate" @ended="onEnded" @loadedmetadata="onLoadedMetadata"></audio>
    
    <!-- Play / Pause Button -->
    <button
      @click="togglePlay"
      class="w-10 h-10 rounded-full bg-slate-900/50 hover:bg-slate-900/80 flex items-center justify-center text-white text-base shadow transition-all shrink-0"
    >
      <span v-if="!isPlaying">▶</span>
      <span v-else>⏸</span>
    </button>

    <!-- Waveform Progress Scrubber -->
    <div class="flex-1 space-y-1 min-w-0">
      <div class="flex items-center space-x-1 h-5 cursor-pointer" @click="seek">
        <div
          v-for="(bar, i) in 18"
          :key="i"
          class="w-1 rounded-full transition-all"
          :style="{ height: `${(i % 3 === 0 ? 16 : (i % 2 === 0 ? 12 : 8))}px` }"
          :class="[
            (currentTime / duration) >= (i / 18)
              ? (isOwn ? 'bg-white' : 'bg-violet-400')
              : (isOwn ? 'bg-violet-400/40' : 'bg-slate-600')
          ]"
        ></div>
      </div>
      <div class="flex items-center justify-between text-[10px]" :class="isOwn ? 'text-violet-200' : 'text-slate-400'">
        <span>🎙️ Voice Note</span>
        <span>{{ formatTime(currentTime) }} / {{ formatTime(duration) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  src: String,
  isOwn: Boolean
})

const audioRef = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)

function togglePlay() {
  if (!audioRef.value) return
  if (isPlaying.value) {
    audioRef.value.pause()
    isPlaying.value = false
  } else {
    audioRef.value.play()
    isPlaying.value = true
  }
}

function onTimeUpdate() {
  if (audioRef.value) {
    currentTime.value = audioRef.value.currentTime
  }
}

function onEnded() {
  isPlaying.value = false
  currentTime.value = 0
}

function onLoadedMetadata() {
  if (audioRef.value) {
    duration.value = audioRef.value.duration || 0
  }
}

function seek(e) {
  if (!audioRef.value || !duration.value) return
  const rect = e.currentTarget.getBoundingClientRect()
  const clickX = e.clientX - rect.left
  const pct = clickX / rect.width
  audioRef.value.currentTime = pct * duration.value
}

function formatTime(sec) {
  if (!sec || isNaN(sec)) return '0:00'
  const m = Math.floor(sec / 60)
  const s = Math.floor(sec % 60)
  return `${m}:${s < 10 ? '0' : ''}${s}`
}
</script>
