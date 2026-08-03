import { defineStore } from 'pinia'
import conversationsService from '../services/conversations'
import messagesService from '../services/messages'
import contactsService from '../services/contacts'
import { getEcho } from '../api/echo'
import { useAuthStore } from './auth'
import { useNotificationSound } from '../composables/useNotificationSound'

export const useChatStore = defineStore('chat', {
  state: () => ({
    conversations: [],
    activeConversation: null,
    messages: [],
    searchResults: [],
    typingUsers: {}, // { [conversationId]: ['Alice', 'Bob'] }
    subscribedChannels: new Set(),
    loading: false,
    messagesLoading: false,
    error: null,
  }),

  getters: {
    activeMessages: (state) => state.messages,
    sortedConversations: (state) => {
      return [...state.conversations].sort((a, b) => {
        const dateA = new Date(a.updated_at || a.created_at)
        const dateB = new Date(b.updated_at || b.created_at)
        return dateB - dateA
      })
    },
    activeTypingUsers: (state) => {
      if (!state.activeConversation) return []
      return state.typingUsers[state.activeConversation.id] || []
    }
  },

  actions: {
    async fetchConversations() {
      this.loading = true
      this.error = null
      try {
        this.conversations = await conversationsService.getConversations()
        // Auto-subscribe to all user's conversations
        this.conversations.forEach(conv => this.subscribeToChannel(conv.id))
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load conversations'
      } finally {
        this.loading = false
      }
    },

    subscribeToChannel(conversationId) {
      if (this.subscribedChannels.has(conversationId)) return

      const echo = getEcho()
      const channel = echo.private(`conversation.${conversationId}`)

      channel
        .listen('.MessageSent', (e) => {
          this.handleIncomingMessage(e)
        })
        .listen('.MessageRead', (e) => {
          this.handleMessageRead(e)
        })
        .listenForWhisper('typing', (e) => {
          this.handleTypingWhisper(conversationId, e.name)
        })

      this.subscribedChannels.add(conversationId)
    },

    handleIncomingMessage(message) {
      const authStore = useAuthStore()
      const { playChime } = useNotificationSound()

      // Play audio chime for non-sender messages
      if (message.sender_id !== authStore.user?.id) {
        playChime()
      }

      // Update active thread
      if (this.activeConversation && this.activeConversation.id === message.conversation_id) {
        // Check if message ID already exists
        const existingByIdx = this.messages.findIndex(m => m.id === message.id)

        if (existingByIdx !== -1) {
          // Message already present, update content/sender info
          this.messages[existingByIdx] = message
        } else {
          // Check if there is an optimistic pending message from current user with matching body
          const optimisticIdx = this.messages.findIndex(
            m => typeof m.id === 'string' && m.id.startsWith('temp-') && m.body === message.body
          )

          if (optimisticIdx !== -1) {
            // Replace optimistic message with confirmed server message
            this.messages[optimisticIdx] = message
          } else {
            // New message from another user or new device -> push
            this.messages.push(message)
          }
        }
      }

      // Update snippet in conversation list
      const conv = this.conversations.find(c => c.id === message.conversation_id)
      if (conv) {
        conv.latestMessage = message
        conv.updated_at = message.created_at
      }
    },

    handleMessageRead({ conversation_id, user_id }) {
      if (this.activeConversation && this.activeConversation.id === conversation_id) {
        this.messages.forEach(m => {
          if (!m.read_receipts) m.read_receipts = []
          if (!m.read_receipts.some(r => r.user_id === user_id)) {
            m.read_receipts.push({ user_id, read_at: new Date().toISOString() })
          }
        })
      }
    },

    handleTypingWhisper(conversationId, userName) {
      if (!this.typingUsers[conversationId]) {
        this.typingUsers[conversationId] = []
      }
      if (!this.typingUsers[conversationId].includes(userName)) {
        this.typingUsers[conversationId].push(userName)
      }

      setTimeout(() => {
        if (this.typingUsers[conversationId]) {
          this.typingUsers[conversationId] = this.typingUsers[conversationId].filter(name => name !== userName)
        }
      }, 3000)
    },

    sendTypingWhisper() {
      if (!this.activeConversation) return
      const authStore = useAuthStore()
      const echo = getEcho()
      const channel = echo.private(`conversation.${this.activeConversation.id}`)
      channel.whisper('typing', {
        name: authStore.user?.name || authStore.user?.username || 'Someone'
      })
    },

    async selectConversation(conversation) {
      this.activeConversation = conversation
      this.messages = []
      this.messagesLoading = true

      this.subscribeToChannel(conversation.id)

      try {
        const data = await messagesService.getMessages(conversation.id)
        this.messages = Array.isArray(data) ? data : (data.data || [])
        this.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
        await messagesService.markAsRead(conversation.id)
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load messages'
      } finally {
        this.messagesLoading = false
      }
    },

    async sendMessage(body) {
      if (!this.activeConversation || !body.trim()) return

      const authStore = useAuthStore()
      
      const tempId = `temp-${Date.now()}`
      const optimisticMessage = {
        id: tempId,
        conversation_id: this.activeConversation.id,
        sender_id: authStore.user?.id,
        sender: authStore.user,
        body: body.trim(),
        type: 'text',
        created_at: new Date().toISOString(),
      }

      this.messages.push(optimisticMessage)

      try {
        const actualMessage = await messagesService.sendMessage(this.activeConversation.id, body.trim())
        
        // Check if actualMessage was already inserted by WebSocket broadcast before API response resolved
        const alreadyAddedIdx = this.messages.findIndex(m => m.id === actualMessage.id)

        if (alreadyAddedIdx !== -1) {
          // Message already inserted by WebSocket -> remove temp optimistic placeholder
          this.messages = this.messages.filter(m => m.id !== tempId)
        } else {
          // Replace temp placeholder with actual server message
          const tempIdx = this.messages.findIndex(m => m.id === tempId)
          if (tempIdx !== -1) {
            this.messages[tempIdx] = actualMessage
          }
        }

        const conv = this.conversations.find(c => c.id === this.activeConversation.id)
        if (conv) {
          conv.latestMessage = actualMessage
          conv.updated_at = new Date().toISOString()
        }
      } catch (err) {
        // Rollback optimistic message on error
        this.messages = this.messages.filter(m => m.id !== tempId)
        this.error = err.response?.data?.message || 'Failed to send message'
      }
    },

    async searchContacts(query) {
      if (!query.trim()) {
        this.searchResults = []
        return
      }
      try {
        this.searchResults = await contactsService.search(query.trim())
      } catch (err) {
        this.searchResults = []
      }
    },

    async startDirectChat(userId) {
      try {
        const conversation = await conversationsService.createConversation({
          type: 'direct',
          user_id: userId
        })
        
        const existingIdx = this.conversations.findIndex(c => c.id === conversation.id)
        if (existingIdx === -1) {
          this.conversations.unshift(conversation)
        } else {
          this.conversations[existingIdx] = conversation
        }

        await this.selectConversation(conversation)
        return conversation
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to start chat'
        throw err
      }
    },

    async createGroupChat(name, participantIds) {
      try {
        const conversation = await conversationsService.createConversation({
          type: 'group',
          name,
          participants: participantIds
        })
        this.conversations.unshift(conversation)
        await this.selectConversation(conversation)
        return conversation
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to create group'
        throw err
      }
    }
  }
})
