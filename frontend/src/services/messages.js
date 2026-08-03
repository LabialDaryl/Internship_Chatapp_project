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

  async markAsRead(conversationId) {
    const response = await client.post(`/conversations/${conversationId}/read`)
    return response.data
  }
}
