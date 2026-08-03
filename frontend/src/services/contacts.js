import client from '../api/client'

export default {
  async search(query) {
    const response = await client.get('/contacts/search', { params: { q: query } })
    return response.data.data
  }
}
