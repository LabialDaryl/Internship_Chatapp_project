<template>
  <AuthBackground>
    <div class="w-full max-w-md sm:max-w-lg glass-card-purple rounded-[30px] p-7 sm:p-9 space-y-6 shadow-2xl animate-fade-in-up">
      <!-- Logo & Header -->
      <div class="text-center space-y-2">
        <div class="flex items-center justify-center mb-1">
          <AppLogo size="lg" :showTagline="true" />
        </div>

        <h1 class="text-2xl font-bold text-white tracking-tight">
          Create Account
        </h1>
        <p class="text-xs text-purple-200/70 font-normal">
          Join EsmiringHOY to start chatting
        </p>
      </div>

      <!-- Error Alert -->
      <div v-if="authStore.error" class="p-3 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-300 text-xs text-center backdrop-blur-sm">
        {{ authStore.error }}
      </div>

      <!-- Registration Form -->
      <form @submit.prevent="handleRegister" class="space-y-3.5">
        <!-- Display Name & Username -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            variant="pill-purple"
            v-model="form.name"
            placeholder="Display Name"
            :error="errors.name"
            required
          >
            <template #icon>
              <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </template>
          </Input>

          <Input
            variant="pill-purple"
            v-model="form.username"
            placeholder="Username"
            :error="errors.username"
            required
          >
            <template #icon>
              <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </template>
          </Input>
        </div>

        <!-- Email -->
        <Input
          variant="pill-purple"
          type="email"
          v-model="form.email"
          placeholder="Email Address"
          :error="errors.email"
          required
        >
          <template #icon>
            <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </template>
        </Input>

        <!-- Date of Birth -->
        <Input
          variant="pill-purple"
          type="date"
          v-model="form.date_of_birth"
          placeholder="Date of Birth"
          :error="errors.date_of_birth"
          required
        >
          <template #icon>
            <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </template>
        </Input>

        <!-- Passwords -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            variant="pill-purple"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            placeholder="Password"
            :error="errors.password"
            required
          >
            <template #icon>
              <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </template>
            <template #suffix>
              <button type="button" @click="showPassword = !showPassword" class="focus:outline-none hover:text-white transition-colors">
                <svg v-if="!showPassword" class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" />
                </svg>
                <svg v-else class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </template>
          </Input>

          <Input
            variant="pill-purple"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password_confirmation"
            placeholder="Confirm Password"
            required
          >
            <template #icon>
              <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </template>
          </Input>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="btn-purple-glow mt-3 flex items-center justify-center gap-2"
          :disabled="authStore.loading"
        >
          <svg v-if="authStore.loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Register & Continue</span>
        </button>
      </form>

      <!-- Divider -->
      <div class="relative flex items-center justify-center my-3">
        <div class="border-t border-purple-500/20 w-full"></div>
        <span class="bg-[#140826] px-3 text-[11px] text-purple-300/60 font-light rounded-full border border-purple-500/10 absolute">
          Or sign up with
        </span>
      </div>

      <!-- Social Logins -->
      <div class="flex items-center gap-3 pt-1">
        <button type="button" class="btn-social-pill">
          <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.3 9 5 12 5z"/>
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
            <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 10.8 0 12s.7 2.3 1.9 4.7l3.7-2.9z"/>
            <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.3-6.4-5.2L1.9 16C3.7 19.7 7.5 23 12 23z"/>
          </svg>
          <span>Google</span>
        </button>

        <button type="button" class="btn-social-pill">
          <svg class="w-4 h-4 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
          <span>Facebook</span>
        </button>
      </div>

      <!-- Footer Link -->
      <div class="text-center text-xs text-purple-200/70 pt-2">
        Already have an account?
        <router-link to="/login" class="text-purple-400 hover:text-purple-300 font-semibold transition-colors ml-1">
          Sign In
        </router-link>
      </div>
    </div>
  </AuthBackground>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AuthBackground from '../components/auth/AuthBackground.vue'
import AppLogo from '../components/base/AppLogo.vue'
import Input from '../components/base/Input.vue'

const router = useRouter()
const authStore = useAuthStore()

const showPassword = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  username: '',
  email: '',
  date_of_birth: '',
  password: '',
  password_confirmation: ''
})

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
