<template>
  <div class="px-4 py-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between animate-fade-in">
    
    <!-- Recording Timer & Pulsing Mic Icon -->
    <div class="flex items-center space-x-3">
      <div class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></div>
      <div class="text-xs font-bold text-rose-400">
        Recording Voice Note... <span class="text-slate-200 ml-2 font-mono">{{ formatTime(seconds) }}</span>
      </div>
    </div>

    <!-- Audio Visualizer Waves -->
    <div class="flex items-center space-x-1">
      <div class="w-1 h-4 bg-violet-500 rounded-full animate-bounce"></div>
      <div class="w-1 h-6 bg-violet-400 rounded-full animate-bounce delay-100"></div>
      <div class="w-1 h-3 bg-violet-600 rounded-full animate-bounce delay-200"></div>
      <div class="w-1 h-5 bg-fuchsia-500 rounded-full animate-bounce delay-150"></div>
    </div>

    <!-- Cancel & Send Buttons -->
    <div class="flex items-center space-x-2">
      <button
        @click="cancel"
        class="px-3 py-1.5 text-xs text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-xl transition-all"
      >
        Cancel
      </button>

      <button
        @click="stopAndSend"
        class="px-4 py-1.5 text-xs bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white font-semibold rounded-xl shadow-lg shadow-violet-600/20 hover:opacity-95 transition-all flex items-center space-x-1.5"
      >
        <span>Send Voice</span>
        <span>🎙️</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const emit = defineEmits(['send', 'cancel'])

const seconds = ref(0)
let timer = null
let mediaRecorder = null
let audioChunks = []

onMounted(async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    audioChunks = []

    mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) audioChunks.push(e.data)
    }

    mediaRecorder.start()

    timer = setInterval(() => {
      seconds.value++
    }, 1000)
  } catch (err) {
    alert('Microphone access denied or not supported.')
    emit('cancel')
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function cancel() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
  emit('cancel')
}

function stopAndSend() {
  if (!mediaRecorder) return

  mediaRecorder.onstop = () => {
    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' })
    emit('send', audioBlob)
  }

  mediaRecorder.stop()
}

function formatTime(s) {
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${m}:${sec < 10 ? '0' : ''}${sec}`
}
</script>
