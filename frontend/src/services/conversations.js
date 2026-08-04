import client from '../api/client'

export default {
  async getConversations() {
    const response = await client.get('/conversations')
    return response.data.data
  },

  async createConversation(data) {
    const response = await client.post('/conversations', data)
    return response.data.data
  },

  async getConversation(id) {
    const response = await client.get(`/conversations/${id}`)
    return response.data.data
  },

  async addParticipant(conversationId, userId) {
    const response = await client.post(`/conversations/${conversationId}/participants`, { user_id: userId })
    return response.data
  },

  async removeParticipant(conversationId, userId) {
    const response = await client.delete(`/conversations/${conversationId}/participants/${userId}`)
    return response.data
  },

  async updateParticipantRole(conversationId, userId, role) {
    const response = await client.put(`/conversations/${conversationId}/participants/${userId}/role`, { role })
    return response.data
  },

  async leaveConversation(id) {
    const response = await client.post(`/conversations/${id}/leave`)
    return response.data
  }
}
