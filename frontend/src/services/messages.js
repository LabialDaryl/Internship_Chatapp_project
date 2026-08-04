import client from '../api/client'

export default {
  async getMessages(conversationId) {
    const response = await client.get(`/conversations/${conversationId}/messages`)
    return response.data
  },

  async sendMessage(conversationId, body, type = 'text', parentId = null) {
    const response = await client.post(`/conversations/${conversationId}/messages`, {
      body,
      type,
      parent_id: parentId
    })
    return response.data.data
  },

  async sendAttachment(conversationId, file, parentId = null) {
    const formData = new FormData()
    formData.append('file', file)
    if (parentId) {
      formData.append('parent_id', parentId)
    }
    const response = await client.post(`/conversations/${conversationId}/attachments`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data.data
  },

  async sendVoiceNote(conversationId, audioBlob, parentId = null) {
    const formData = new FormData()
    formData.append('audio', audioBlob, 'voicenote.webm')
    if (parentId) {
      formData.append('parent_id', parentId)
    }
    const response = await client.post(`/conversations/${conversationId}/voice-notes`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data.data
  },

  async toggleReaction(conversationId, messageId, emoji) {
    const response = await client.post(`/conversations/${conversationId}/messages/${messageId}/reactions`, { emoji })
    return response.data.data
  },

  async togglePinMessage(conversationId, messageId) {
    const response = await client.post(`/conversations/${conversationId}/messages/${messageId}/pin`)
    return response.data.data
  },

  async getMessageReadReceipts(messageId) {
    const response = await client.get(`/messages/${messageId}/read-receipts`)
    return response.data.data
  },

  async getConversationMedia(conversationId) {
    const response = await client.get(`/conversations/${conversationId}/media`)
    return response.data.data
  },

  async sendCallSignal(conversationId, action, data = null) {
    const response = await client.post(`/conversations/${conversationId}/call-signal`, { action, data })
    return response.data
  },

  async logCall(conversationId, type, status, durationSeconds = 0) {
    const response = await client.post(`/conversations/${conversationId}/call-logs`, {
      type,
      status,
      duration_seconds: durationSeconds
    })
    return response.data
  },

  async updateMessage(conversationId, messageId, body) {
    const response = await client.put(`/conversations/${conversationId}/messages/${messageId}`, { body })
    return response.data.data
  },

  async deleteMessage(conversationId, messageId) {
    const response = await client.delete(`/conversations/${conversationId}/messages/${messageId}`)
    return response.data
  },

  async forwardMessage(conversationId, messageId, targetConversationId) {
    const response = await client.post(`/conversations/${conversationId}/messages/${messageId}/forward`, {
      target_conversation_id: targetConversationId
    })
    return response.data.data
  },

  async searchMessages(conversationId, query) {
    const response = await client.get(`/conversations/${conversationId}/search-messages`, {
      params: { query }
    })
    return response.data
  },

  async markAsRead(conversationId) {
    const response = await client.post(`/conversations/${conversationId}/read`)
    return response.data
  }
}
