<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 via-slate-950 to-primary-950">
    <div class="w-full max-w-md glass-panel p-8 rounded-2xl shadow-2xl space-y-6 animate-fade-in-up">
      <div class="text-center space-y-2">
        <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-primary-400 to-secondary-500 bg-clip-text text-transparent">
          Welcome Back
        </h1>
        <p class="text-slate-400 text-sm">Sign in to your Chatapp account</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div v-if="authStore.error" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ authStore.error }}
        </div>

        <Input
          label="Email Address"
          type="email"
          v-model="form.email"
          placeholder="you@example.com"
          required
        />

        <Input
          label="Password"
          type="password"
          v-model="form.password"
          placeholder="••••••••"
          required
        />

        <Button
          type="submit"
          variant="primary"
          class="w-full py-3"
          :loading="authStore.loading"
        >
          Sign In
        </Button>
      </form>

      <div class="text-center text-sm text-slate-400">
        Don't have an account?
        <router-link to="/register" class="text-primary-400 hover:text-primary-300 font-semibold underline ml-1">
          Create Account
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Button from '../components/base/Button.vue'
import Input from '../components/base/Input.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const handleLogin = async () => {
  try {
    await authStore.login(form)
    router.push({ name: 'chat' })
  } catch (err) {
    // Error state handled in store
  }
}
</script>
