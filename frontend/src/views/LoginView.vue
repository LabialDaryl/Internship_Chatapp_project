<template>
  <AuthBackground>
    <div class="w-full max-w-sm sm:max-w-md glass-card-purple rounded-[30px] p-7 sm:p-9 space-y-6 shadow-2xl animate-fade-in-up">
      <!-- Logo & Header -->
      <div class="text-center space-y-2">
        <div class="flex items-center justify-center mb-1">
          <AppLogo size="lg" :showTagline="true" />
        </div>

        <h1 class="text-2xl font-bold text-white tracking-tight">
          Welcome Back!
        </h1>
        <p class="text-xs text-purple-200/70 font-normal">
          Sign in to your account
        </p>
      </div>

      <!-- Error Alert -->
      <div v-if="authStore.error" class="p-3 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-300 text-xs text-center backdrop-blur-sm">
        {{ authStore.error }}
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Username or Email -->
        <Input
          variant="pill-purple"
          type="text"
          v-model="form.email"
          placeholder="Username or Email"
          required
        >
          <template #icon>
            <!-- User Outline Icon -->
            <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </template>
        </Input>

        <!-- Password -->
        <div>
          <Input
            variant="pill-purple"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            placeholder="Password"
            required
          >
            <template #icon>
              <!-- Lock Outline Icon -->
              <svg class="w-4 h-4 text-purple-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </template>
            <template #suffix>
              <!-- Password Toggle Icon -->
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

          <!-- Forgot Password Link -->
          <div class="text-right mt-1.5 pr-1">
            <a href="#" @click.prevent class="text-[11px] text-purple-300/70 hover:text-purple-200 transition-colors">
              Forgot Password?
            </a>
          </div>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="btn-purple-glow mt-2 flex items-center justify-center gap-2"
          :disabled="authStore.loading"
        >
          <svg v-if="authStore.loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Log In</span>
        </button>
      </form>

      <!-- Divider -->
      <div class="relative flex items-center justify-center my-4">
        <div class="border-t border-purple-500/20 w-full"></div>
        <span class="bg-[#140826] px-3 text-[11px] text-purple-300/60 font-light rounded-full border border-purple-500/10 absolute">
          Or log in with
        </span>
      </div>

      <!-- Social Logins -->
      <div class="flex items-center gap-3 pt-1">
        <button type="button" class="btn-social-pill">
          <!-- Google G Logo -->
          <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.3 9 5 12 5z"/>
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
            <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 10.8 0 12s.7 2.3 1.9 4.7l3.7-2.9z"/>
            <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.3-6.4-5.2L1.9 16C3.7 19.7 7.5 23 12 23z"/>
          </svg>
          <span>Google</span>
        </button>

        <button type="button" class="btn-social-pill">
          <!-- Facebook f Logo -->
          <svg class="w-4 h-4 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
          <span>Facebook</span>
        </button>
      </div>

      <!-- Footer Link -->
      <div class="text-center text-xs text-purple-200/70 pt-2">
        Don't have an account?
        <router-link to="/register" class="text-purple-400 hover:text-purple-300 font-semibold transition-colors ml-1">
          Sign Up
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

const form = reactive({
  email: '',
  password: ''
})

const handleLogin = async () => {
  try {
    await authStore.login(form)
    router.push({ name: 'chat' })
  } catch (err) {
    // Handled in store
  }
}
</script>
