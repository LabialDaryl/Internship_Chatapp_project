<template>
  <div class="flex h-screen w-screen overflow-hidden bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <!-- Left Navigation Sidebar -->
    <ChatSidebar
      @open-new-chat="isModalOpen = true"
      @open-create-group="isGroupModalOpen = true"
      @open-profile="isProfileModalOpen = true"
      @open-settings="isSettingsModalOpen = true"
      @open-logout-modal="isLogoutModalOpen = true"
    />

    <!-- Conversations List Panel -->
    <div
      :class="[
        'h-full border-r border-slate-200 dark:border-slate-800',
        chatStore.activeConversation && isMobile() ? 'hidden' : 'block'
      ]"
    >
      <ConversationList @open-new-chat="isModalOpen = true" />
    </div>

    <!-- Active Message Thread Panel -->
    <div
      :class="[
        'flex-1 flex flex-col h-full bg-slate-100 dark:bg-slate-950',
        !chatStore.activeConversation && isMobile() ? 'hidden' : 'flex'
      ]"
    >
      <!-- Active Chat State -->
      <template v-if="chatStore.activeConversation">
        <ChatHeader
          :conversation="chatStore.activeConversation"
          @back="chatStore.activeConversation = null"
          @open-group-details="isGroupDetailsOpen = true"
          @open-search="isSearchModalOpen = true"
          @open-media-gallery="isMediaGalleryOpen = true"
          @open-nicknames="isNicknamesModalOpen = true"
          @start-call="handleStartCall"
        />

        <MessageList
          :messages="chatStore.activeMessages"
          :loading="chatStore.messagesLoading"
          :isGroup="chatStore.activeConversation.type === 'group'"
          @open-forward="openForwardModal"
        />

        <MessageInput @send="chatStore.sendMessage" />
      </template>

      <!-- Empty State (No Chat Selected) -->
      <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-500 space-y-4">
        <div class="w-16 h-16 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-3xl shadow-lg">
          💬
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">Select a conversation</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mt-1">Choose a chat from the sidebar or start a new conversation to begin messaging.</p>
        </div>
        <div class="flex space-x-3">
          <Button variant="primary" @click="isModalOpen = true">
            Start Direct Chat
          </Button>
          <Button variant="secondary" @click="isGroupModalOpen = true">
            Create Group Chat
          </Button>
        </div>
      </div>
    </div>

    <!-- Modals & Drawers -->
    <NewChatModal :isOpen="isModalOpen" @close="isModalOpen = false" />
    
    <CreateGroupModal
      :show="isGroupModalOpen"
      :contacts="chatStore.searchResults"
      @close="isGroupModalOpen = false"
    />

    <GroupDetailsModal
      :show="isGroupDetailsOpen"
      :conversation="chatStore.activeConversation"
      @close="isGroupDetailsOpen = false"
      @leave="handleLeaveGroup"
      @open-nicknames="isNicknamesModalOpen = true"
    />

    <NicknamesModal
      :show="isNicknamesModalOpen"
      :conversation="chatStore.activeConversation"
      @close="isNicknamesModalOpen = false"
    />

    <ProfileModal
      :show="isProfileModalOpen"
      @close="isProfileModalOpen = false"
    />

    <SettingsModal
      :show="isSettingsModalOpen"
      @close="isSettingsModalOpen = false"
      @open-profile="isProfileModalOpen = true"
    />

    <LogoutModal
      :show="isLogoutModalOpen"
      @close="isLogoutModalOpen = false"
      @confirm="handleConfirmLogout"
    />

    <ForwardMessageModal
      :show="isForwardModalOpen"
      :message="forwardTargetMessage"
      :conversations="chatStore.conversations"
      @close="isForwardModalOpen = false"
      @forward="handleForwardSubmit"
    />

    <MessageSearchModal
      :show="isSearchModalOpen"
      @close="isSearchModalOpen = false"
      @select="handleSearchSelect"
    />

    <ChatMediaGallery
      :show="isMediaGalleryOpen"
      :conversationId="chatStore.activeConversation?.id"
      @close="isMediaGalleryOpen = false"
    />

    <!-- WebRTC Calling Modals -->
    <IncomingCallModal
      :show="isIncomingCallOpen"
      :caller="callPartner"
      :callType="activeCallType"
      @accept="handleAcceptCall"
      @decline="handleDeclineCall"
    />

    <ActiveCallModal
      :show="isActiveCallOpen"
      :partnerName="callPartner?.name || 'Call Partner'"
      :callType="activeCallType"
      @end="handleEndCall"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'
import { useBreakpoints } from '../composables/useBreakpoints'
import ChatSidebar from '../components/chat/ChatSidebar.vue'
import ConversationList from '../components/chat/ConversationList.vue'
import ChatHeader from '../components/chat/ChatHeader.vue'
import MessageList from '../components/chat/MessageList.vue'
import MessageInput from '../components/chat/MessageInput.vue'
import NewChatModal from '../components/chat/NewChatModal.vue'
import CreateGroupModal from '../components/chat/CreateGroupModal.vue'
import GroupDetailsModal from '../components/chat/GroupDetailsModal.vue'
import ProfileModal from '../components/profile/ProfileModal.vue'
import ForwardMessageModal from '../components/chat/ForwardMessageModal.vue'
import MessageSearchModal from '../components/chat/MessageSearchModal.vue'
import ChatMediaGallery from '../components/chat/ChatMediaGallery.vue'
import IncomingCallModal from '../components/chat/IncomingCallModal.vue'
import ActiveCallModal from '../components/chat/ActiveCallModal.vue'
import Button from '../components/base/Button.vue'
import conversationsService from '../services/conversations'
import messagesService from '../services/messages'
import webrtcManager from '../services/webrtc'

import { useRouter } from 'vue-router'
import SettingsModal from '../components/settings/SettingsModal.vue'
import LogoutModal from '../components/auth/LogoutModal.vue'
import NicknamesModal from '../components/chat/NicknamesModal.vue'

const chatStore = useChatStore()
const authStore = useAuthStore()
const router = useRouter()
const { isMobile } = useBreakpoints()

const isModalOpen = ref(false)
const isGroupModalOpen = ref(false)
const isGroupDetailsOpen = ref(false)
const isProfileModalOpen = ref(false)
const isSettingsModalOpen = ref(false)
const isLogoutModalOpen = ref(false)
const isNicknamesModalOpen = ref(false)
const isForwardModalOpen = ref(false)
const isSearchModalOpen = ref(false)
const isMediaGalleryOpen = ref(false)

const handleConfirmLogout = async () => {
  isLogoutModalOpen.value = false
  await authStore.logout()
  router.push({ name: 'login' })
}
const forwardTargetMessage = ref(null)

// Call State
const isIncomingCallOpen = ref(false)
const isActiveCallOpen = ref(false)
const activeCallType = ref('video')
const callPartner = ref(null)
const incomingOffer = ref(null)
let callStartTime = 0

onMounted(async () => {
  await chatStore.fetchConversations()
  await chatStore.searchContacts('a')

  chatStore.registerCallSignalHandler(handleWebRTCSignal)
})

const handleStartCall = async (type) => {
  if (!chatStore.activeConversation) return

  activeCallType.value = type
  const other = chatStore.activeConversation.participants?.find(p => p.user_id !== authStore.user?.id)
  callPartner.value = other?.user || { name: 'Chat Member' }
  callStartTime = Date.now()

  try {
    await webrtcManager.initLocalStream(type === 'video', true)
    webrtcManager.onIceCandidate = (candidate) => {
      messagesService.sendCallSignal(chatStore.activeConversation.id, 'ice', candidate)
    }

    const offer = await webrtcManager.createOffer()
    await messagesService.sendCallSignal(chatStore.activeConversation.id, 'initiate', {
      type,
      offer,
      caller: authStore.user
    })

    isActiveCallOpen.value = true
  } catch {
    chatStore.showToast('Camera / Microphone permission denied.')
  }
}

const handleWebRTCSignal = async ({ conversation_id, sender_id, action, data }) => {
  if (sender_id === authStore.user?.id) return

  if (action === 'initiate') {
    activeCallType.value = data.type || 'video'
    callPartner.value = data.caller || { name: 'Incoming Call' }
    incomingOffer.value = data.offer
    isIncomingCallOpen.value = true
    chatStore.showToast(`🔔 Incoming ${data.type || 'video'} call from ${callPartner.value.name}`)
  } else if (action === 'accept') {
    if (data?.answer) {
      await webrtcManager.handleAnswer(data.answer)
      chatStore.showToast('✅ Call connected')
    }
  } else if (action === 'decline') {
    isIncomingCallOpen.value = false
    isActiveCallOpen.value = false
    webrtcManager.close()
    chatStore.showToast(`🚫 ${callPartner.value?.name || 'User'} declined the call`)
  } else if (action === 'end') {
    isIncomingCallOpen.value = false
    isActiveCallOpen.value = false
    webrtcManager.close()
    chatStore.showToast('📞 Call ended')
  } else if (action === 'ice' && data) {
    await webrtcManager.addIceCandidate(data)
  }
}

const handleAcceptCall = async () => {
  isIncomingCallOpen.value = false
  callStartTime = Date.now()

  try {
    await webrtcManager.initLocalStream(activeCallType.value === 'video', true)
    webrtcManager.onIceCandidate = (candidate) => {
      if (chatStore.activeConversation) {
        messagesService.sendCallSignal(chatStore.activeConversation.id, 'ice', candidate)
      }
    }

    const answer = await webrtcManager.handleOffer(incomingOffer.value)
    if (chatStore.activeConversation) {
      await messagesService.sendCallSignal(chatStore.activeConversation.id, 'accept', { answer })
    }

    isActiveCallOpen.value = true
  } catch {
    chatStore.showToast('Failed to access media devices.')
  }
}

const handleDeclineCall = async () => {
  isIncomingCallOpen.value = false
  chatStore.showToast('🚫 Call declined')
  if (chatStore.activeConversation) {
    await messagesService.sendCallSignal(chatStore.activeConversation.id, 'decline')
    const res = await messagesService.logCall(chatStore.activeConversation.id, activeCallType.value, 'declined', 0)
    if (res?.data) {
      chatStore.fetchConversations()
    }
  }
}

const handleEndCall = async () => {
  isActiveCallOpen.value = false
  const durationSec = Math.floor((Date.now() - callStartTime) / 1000)

  webrtcManager.close()

  if (chatStore.activeConversation) {
    await messagesService.sendCallSignal(chatStore.activeConversation.id, 'end')
    await messagesService.logCall(chatStore.activeConversation.id, activeCallType.value, 'completed', durationSec)
  }
}

const openForwardModal = (msg) => {
  forwardTargetMessage.value = msg
  isForwardModalOpen.value = true
}

const handleForwardSubmit = async ({ messageId, targetConversationId }) => {
  await chatStore.forwardMessage(messageId, targetConversationId)
}

const handleSearchSelect = (msg) => {
  const el = document.getElementById(`message-${msg.id}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el.classList.add('ring-2', 'ring-violet-500', 'bg-violet-600/20')
    setTimeout(() => {
      el.classList.remove('ring-2', 'ring-violet-500', 'bg-violet-600/20')
    }, 2000)
  }
}

const handleLeaveGroup = async (conversationId) => {
  try {
    await conversationsService.leaveConversation(conversationId)
    chatStore.conversations = chatStore.conversations.filter(c => c.id !== conversationId)
    chatStore.activeConversation = null
  } catch (e) {
    chatStore.error = 'Failed to leave conversation'
  }
}
</script>
