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
    onlineUserIds: [], // Array of online user IDs
    subscribedChannels: new Set(),
    isPresenceSubscribed: false,
    isRecordingVoice: false,
    replyingToMessage: null,
    editingMessage: null,
    toastMessage: null,
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
    },
    isUserOnline: (state) => (userId) => {
      return state.onlineUserIds.includes(userId)
    }
  },

  actions: {
    showToast(text) {
      this.toastMessage = text
      setTimeout(() => {
        if (this.toastMessage === text) this.toastMessage = null
      }, 2500)
    },

    async fetchConversations() {
      this.loading = true
      this.error = null
      try {
        this.conversations = await conversationsService.getConversations()
        this.conversations.forEach(conv => this.subscribeToChannel(conv.id))
        this.subscribePresence()
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load conversations'
      } finally {
        this.loading = false
      }
    },

    subscribePresence() {
      if (this.isPresenceSubscribed) return
      try {
        const echo = getEcho()
        echo.join('presence-chat')
          .here((users) => {
            this.onlineUserIds = users.map(u => u.id)
          })
          .joining((user) => {
            if (!this.onlineUserIds.includes(user.id)) {
              this.onlineUserIds.push(user.id)
            }
          })
          .leaving((user) => {
            this.onlineUserIds = this.onlineUserIds.filter(id => id !== user.id)
          })
        this.isPresenceSubscribed = true
      } catch (e) {
        // Presence channel fallback
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
        .listen('.MessageUpdated', (e) => {
          this.handleMessageUpdated(e)
        })
        .listen('.MessageDeleted', (e) => {
          this.handleMessageDeleted(e)
        })
        .listen('.MessageReactionUpdated', (e) => {
          this.handleReactionUpdated(e)
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

      if (message.sender_id !== authStore.user?.id) {
        playChime()
      }

      if (this.activeConversation && this.activeConversation.id === message.conversation_id) {
        const existingByIdx = this.messages.findIndex(m => m.id === message.id)

        if (existingByIdx !== -1) {
          this.messages[existingByIdx] = message
        } else {
          const optimisticIdx = this.messages.findIndex(
            m => typeof m.id === 'string' && m.id.startsWith('temp-') && m.body === message.body
          )

          if (optimisticIdx !== -1) {
            this.messages[optimisticIdx] = message
          } else {
            this.messages.push(message)
          }
        }
      }

      const conv = this.conversations.find(c => c.id === message.conversation_id)
      if (conv) {
        conv.latestMessage = message
        conv.updated_at = message.created_at
      }
    },

    handleMessageUpdated(updatedMessage) {
      if (this.activeConversation && this.activeConversation.id === updatedMessage.conversation_id) {
        const idx = this.messages.findIndex(m => m.id === updatedMessage.id)
        if (idx !== -1) {
          this.messages[idx] = updatedMessage
        }
      }
    },

    handleMessageDeleted({ id, conversation_id }) {
      if (this.activeConversation && this.activeConversation.id === conversation_id) {
        const idx = this.messages.findIndex(m => m.id === id)
        if (idx !== -1) {
          this.messages[idx].body = 'This message was deleted'
          this.messages[idx].is_deleted = true
        }
      }
    },

    handleReactionUpdated({ id, conversation_id, reactions }) {
      if (this.activeConversation && this.activeConversation.id === conversation_id) {
        const idx = this.messages.findIndex(m => m.id === id)
        if (idx !== -1) {
          this.messages[idx].reactions = reactions
        }
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
      this.replyingToMessage = null
      this.editingMessage = null
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
      const parentId = this.replyingToMessage?.id || null
      const parentObj = this.replyingToMessage
      
      const tempId = `temp-${Date.now()}`
      const optimisticMessage = {
        id: tempId,
        conversation_id: this.activeConversation.id,
        parent_id: parentId,
        parent: parentObj,
        sender_id: authStore.user?.id,
        sender: authStore.user,
        body: body.trim(),
        type: 'text',
        created_at: new Date().toISOString(),
      }

      this.messages.push(optimisticMessage)
      this.replyingToMessage = null

      try {
        const actualMessage = await messagesService.sendMessage(this.activeConversation.id, body.trim(), 'text', parentId)
        
        const alreadyAddedIdx = this.messages.findIndex(m => m.id === actualMessage.id)

        if (alreadyAddedIdx !== -1) {
          this.messages = this.messages.filter(m => m.id !== tempId)
        } else {
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
        this.messages = this.messages.filter(m => m.id !== tempId)
        this.error = err.response?.data?.message || 'Failed to send message'
      }
    },

    async sendVoiceNote(audioBlob) {
      if (!this.activeConversation) return

      try {
        const parentId = this.replyingToMessage?.id || null
        const message = await messagesService.sendVoiceNote(this.activeConversation.id, audioBlob, parentId)
        this.handleIncomingMessage(message)
        this.replyingToMessage = null
        this.isRecordingVoice = false
        this.showToast('Voice note sent!')
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to send voice note'
      }
    },

    async toggleReaction(messageId, emoji) {
      if (!this.activeConversation) return

      try {
        const updatedReactions = await messagesService.toggleReaction(this.activeConversation.id, messageId, emoji)
        const idx = this.messages.findIndex(m => m.id === messageId)
        if (idx !== -1) {
          this.messages[idx].reactions = updatedReactions
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to toggle reaction'
      }
    },

    async editMessage(messageId, newBody) {
      if (!this.activeConversation || !newBody.trim()) return

      try {
        const updatedMessage = await messagesService.updateMessage(this.activeConversation.id, messageId, newBody.trim())
        const idx = this.messages.findIndex(m => m.id === messageId)
        if (idx !== -1) {
          this.messages[idx] = updatedMessage
        }
        this.editingMessage = null
        this.showToast('Message updated!')
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to edit message'
      }
    },

    async deleteMessage(messageId) {
      if (!this.activeConversation) return

      try {
        await messagesService.deleteMessage(this.activeConversation.id, messageId)
        const idx = this.messages.findIndex(m => m.id === messageId)
        if (idx !== -1) {
          this.messages[idx].body = 'This message was deleted'
          this.messages[idx].is_deleted = true
        }
        this.showToast('Message deleted')
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to delete message'
      }
    },

    async forwardMessage(messageId, targetConversationId) {
      if (!this.activeConversation) return

      try {
        await messagesService.forwardMessage(this.activeConversation.id, messageId, targetConversationId)
        this.showToast('Message forwarded successfully!')
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to forward message'
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
