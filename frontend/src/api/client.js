import axios from 'axios'
import { getEcho } from './echo'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Attach Bearer token & X-Socket-ID header to every request
client.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Socket ID for Laravel broadcast()->toOthers()
  try {
    const socketId = getEcho()?.socketId?.()
    if (socketId) {
      config.headers['X-Socket-ID'] = socketId
    }
  } catch (e) {
    // Ignore if Echo is initializing
  }

  return config
})

// Handle 401 responses globally
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default client
