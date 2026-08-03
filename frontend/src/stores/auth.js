import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import { disconnectEcho } from '@/api/echo'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const errors = ref({})

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')
  const userAvatar = computed(() => user.value?.avatar_url || null)

  // Actions
  async function register(formData) {
    loading.value = true
    errors.value = {}
    try {
      disconnectEcho()
      const { data } = await authApi.register(formData)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('auth_token', data.token)
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function login(formData) {
    loading.value = true
    errors.value = {}
    try {
      disconnectEcho()
      const { data } = await authApi.login(formData)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('auth_token', data.token)
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await authApi.getUser()
      user.value = data.user
    } catch {
      logout()
    }
  }

  async function updateProfile(formData) {
    loading.value = true
    errors.value = {}
    try {
      const { data } = await authApi.updateProfile(formData)
      user.value = data.user
      return data
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (e) {
      // Ignore network logout errors
    } finally {
      disconnectEcho()
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  return {
    user,
    token,
    loading,
    errors,
    isAuthenticated,
    userName,
    userEmail,
    userAvatar,
    register,
    login,
    logout,
    fetchUser,
    updateProfile
  }
})
