<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md animate-fade-in">
    <div class="w-full max-w-4xl h-[85vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden relative">
      
      <!-- Call Header Bar -->
      <div class="px-6 py-4 bg-slate-900/80 border-b border-slate-800/80 flex items-center justify-between z-20">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
          <div>
            <h4 class="text-sm font-bold text-slate-100">{{ partnerName }}</h4>
            <p class="text-[11px] text-slate-400 font-mono">{{ formatTimer(duration) }}</p>
          </div>
        </div>
      </div>

      <!-- Video Stage Container -->
      <div class="flex-1 relative bg-slate-950 flex items-center justify-center overflow-hidden">
        
        <!-- Remote Video Stream -->
        <video
          ref="remoteVideoRef"
          autoplay
          playsinline
          class="w-full h-full object-cover"
        ></video>

        <!-- Remote Audio Stream Fallback -->
        <audio ref="remoteAudioRef" autoplay></audio>

        <!-- Placeholder when remote video disabled -->
        <div v-if="!hasRemoteVideo" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90 space-y-4">
          <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-violet-600 to-fuchsia-600 flex items-center justify-center text-4xl font-bold text-white shadow-2xl">
            {{ partnerName?.charAt(0) || 'U' }}
          </div>
          <p class="text-sm font-medium text-slate-300">{{ partnerName }}</p>
        </div>

        <!-- Local Video Preview (Picture in Picture) -->
        <div class="absolute bottom-6 right-6 w-44 h-32 rounded-2xl overflow-hidden border-2 border-violet-500/80 shadow-2xl bg-slate-900 z-20">
          <video
            ref="localVideoRef"
            autoplay
            playsinline
            muted
            class="w-full h-full object-cover transform -scale-x-100"
          ></video>
        </div>
      </div>

      <!-- Call Control Floating Toolbar -->
      <div class="px-6 py-4 bg-slate-900/90 border-t border-slate-800 flex items-center justify-center space-x-4 z-20">
        
        <!-- Mic Toggle Button -->
        <button
          @click="toggleMic"
          :class="[
            'w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all shadow-md',
            isMuted ? 'bg-rose-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'
          ]"
          :title="isMuted ? 'Unmute Mic' : 'Mute Mic'"
        >
          {{ isMuted ? '🔇' : '🎙️' }}
        </button>

        <!-- Camera Toggle Button -->
        <button
          @click="toggleCamera"
          :class="[
            'w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all shadow-md',
            isVideoOff ? 'bg-rose-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'
          ]"
          :title="isVideoOff ? 'Turn On Camera' : 'Turn Off Camera'"
        >
          {{ isVideoOff ? '📷' : '📹' }}
        </button>

        <!-- Screen Share Button -->
        <button
          @click="toggleScreenShare"
          :class="[
            'w-12 h-12 rounded-full flex items-center justify-center text-lg transition-all shadow-md',
            isScreenSharing ? 'bg-violet-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'
          ]"
          title="Share Screen"
        >
          🖥️
        </button>

        <!-- End Call Button -->
        <button
          @click="emit('end')"
          class="w-14 h-14 rounded-full bg-rose-600 hover:bg-rose-500 text-white flex items-center justify-center text-xl shadow-lg shadow-rose-600/30 transform hover:scale-105 transition-all ml-4"
          title="End Call"
        >
          📞
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import webrtcManager from '../../services/webrtc'

const props = defineProps({
  show: Boolean,
  partnerName: String,
  callType: {
    type: String,
    default: 'video'
  }
})

const emit = defineEmits(['end'])

const localVideoRef = ref(null)
const remoteVideoRef = ref(null)
const remoteAudioRef = ref(null)

const isMuted = ref(false)
const isVideoOff = ref(false)
const isScreenSharing = ref(false)
const hasRemoteVideo = ref(false)

const duration = ref(0)
let timer = null

const attachLocalStream = () => {
  if (localVideoRef.value && webrtcManager.localStream) {
    localVideoRef.value.srcObject = webrtcManager.localStream
    localVideoRef.value.play().catch(() => {})
  }
}

const attachRemoteStream = (stream) => {
  if (remoteVideoRef.value && stream) {
    remoteVideoRef.value.srcObject = stream
    hasRemoteVideo.value = stream.getVideoTracks().length > 0
    remoteVideoRef.value.play().catch(() => {})
  }
  if (remoteAudioRef.value && stream) {
    remoteAudioRef.value.srcObject = stream
    remoteAudioRef.value.play().catch(() => {})
  }
}

onMounted(() => {
  timer = setInterval(() => {
    if (props.show) duration.value++
  }, 1000)

  webrtcManager.onRemoteStream = (stream) => {
    nextTick(() => {
      attachRemoteStream(stream)
    })
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

watch(() => props.show, (val) => {
  if (val) {
    nextTick(() => {
      attachLocalStream()
      if (webrtcManager.remoteStream) {
        attachRemoteStream(webrtcManager.remoteStream)
      }
    })
  }
})

function toggleMic() {
  isMuted.value = !isMuted.value
  webrtcManager.toggleAudio(!isMuted.value)
}

function toggleCamera() {
  isVideoOff.value = !isVideoOff.value
  webrtcManager.toggleVideo(!isVideoOff.value)
}

async function toggleScreenShare() {
  if (!isScreenSharing.value) {
    try {
      await webrtcManager.startScreenShare()
      isScreenSharing.value = true
    } catch {
      isScreenSharing.value = false
    }
  } else {
    webrtcManager.stopScreenShare()
    isScreenSharing.value = false
  }
}

function formatTimer(s) {
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${m}:${sec < 10 ? '0' : ''}${sec}`
}
</script>
