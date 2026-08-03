<template>
  <div class="flex h-screen w-screen overflow-hidden bg-slate-950 text-slate-100">
    <!-- Left Navigation Sidebar -->
    <ChatSidebar
      @open-new-chat="isModalOpen = true"
      @open-create-group="isGroupModalOpen = true"
      @open-profile="isProfileModalOpen = true"
    />

    <!-- Conversations List Panel -->
    <div
      :class="[
        'h-full border-r border-slate-800',
        chatStore.activeConversation && isMobile() ? 'hidden' : 'block'
      ]"
    >
      <ConversationList @open-new-chat="isModalOpen = true" />
    </div>

    <!-- Active Message Thread Panel -->
    <div
      :class="[
        'flex-1 flex flex-col h-full bg-slate-950',
        !chatStore.activeConversation && isMobile() ? 'hidden' : 'flex'
      ]"
    >
      <!-- Active Chat State -->
      <template v-if="chatStore.activeConversation">
        <ChatHeader
          :conversation="chatStore.activeConversation"
          @back="chatStore.activeConversation = null"
          @open-group-details="isGroupDetailsOpen = true"
        />

        <MessageList
          :messages="chatStore.activeMessages"
          :loading="chatStore.messagesLoading"
          :isGroup="chatStore.activeConversation.type === 'group'"
        />

        <MessageInput @send="chatStore.sendMessage" />
      </template>

      <!-- Empty State (No Chat Selected) -->
      <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-500 space-y-4">
        <div class="w-16 h-16 rounded-full bg-slate-900 flex items-center justify-center text-3xl">
          💬
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-300">Select a conversation</h3>
          <p class="text-sm text-slate-500 max-w-sm mt-1">Choose a chat from the sidebar or start a new conversation to begin messaging.</p>
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

    <!-- Modals -->
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
    />

    <ProfileModal
      :show="isProfileModalOpen"
      @close="isProfileModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useChatStore } from '../stores/chat'
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
import Button from '../components/base/Button.vue'
import conversationsService from '../services/conversations'

const chatStore = useChatStore()
const { isMobile } = useBreakpoints()

const isModalOpen = ref(false)
const isGroupModalOpen = ref(false)
const isGroupDetailsOpen = ref(false)
const isProfileModalOpen = ref(false)

onMounted(async () => {
  await chatStore.fetchConversations()
  await chatStore.searchContacts('a') // pre-fetch contact list for group modal
})

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
