import client from './client'

export const authApi = {
  register(data) {
    return client.post('/register', data)
  },

  login(data) {
    return client.post('/login', data)
  },

  logout() {
    return client.post('/logout')
  },

  getUser() {
    return client.get('/user')
  },

  updateProfile(data) {
    return client.put('/user/profile', data)
  },

  updatePassword(data) {
    return client.put('/user/password', data)
  },
}
