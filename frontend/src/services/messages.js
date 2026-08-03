import client from '../api/client'

export default {
  async getMessages(conversationId) {
    const response = await client.get(`/conversations/${conversationId}/messages`)
    return response.data
  },

  async sendMessage(conversationId, body, type = 'text') {
    const response = await client.post(`/conversations/${conversationId}/messages`, { body, type })
    return response.data.data
  },

  async sendAttachment(conversationId, file) {
    const formData = new FormData()
    formData.append('file', file)
    const response = await client.post(`/conversations/${conversationId}/attachments`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data.data
  },

  async markAsRead(conversationId) {
    const response = await client.post(`/conversations/${conversationId}/read`)
    return response.data
  }
}
