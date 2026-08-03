<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 via-slate-950 to-primary-950">
    <div class="w-full max-w-lg glass-panel p-8 rounded-2xl shadow-2xl space-y-6 animate-fade-in-up">
      <div class="text-center space-y-2">
        <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-primary-400 to-secondary-500 bg-clip-text text-transparent">
          Create Account
        </h1>
        <p class="text-slate-400 text-sm">Join Chatapp to start messaging</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div v-if="authStore.error" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ authStore.error }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            label="Display Name"
            v-model="form.name"
            placeholder="John Doe"
            :error="errors.name"
            required
          />

          <Input
            label="Username"
            v-model="form.username"
            placeholder="johndoe"
            :error="errors.username"
            required
          />
        </div>

        <Input
          label="Email Address"
          type="email"
          v-model="form.email"
          placeholder="you@example.com"
          :error="errors.email"
          required
        />

        <Input
          label="Date of Birth"
          type="date"
          v-model="form.date_of_birth"
          :error="errors.date_of_birth"
          required
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            label="Password"
            type="password"
            v-model="form.password"
            placeholder="••••••••"
            :error="errors.password"
            required
          />

          <Input
            label="Confirm Password"
            type="password"
            v-model="form.password_confirmation"
            placeholder="••••••••"
            required
          />
        </div>

        <Button
          type="submit"
          variant="primary"
          class="w-full py-3 mt-2"
          :loading="authStore.loading"
        >
          Register & Continue
        </Button>
      </form>

      <div class="text-center text-sm text-slate-400">
        Already have an account?
        <router-link to="/login" class="text-primary-400 hover:text-primary-300 font-semibold underline ml-1">
          Sign In
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Button from '../components/base/Button.vue'
import Input from '../components/base/Input.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  username: '',
  email: '',
  date_of_birth: '',
  password: '',
  password_confirmation: ''
})

const errors = ref({})

const handleRegister = async () => {
  errors.value = {}
  try {
    await authStore.register(form)
    router.push({ name: 'chat' })
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([k, v]) => [k, v[0]])
      )
    }
  }
}
</script>
