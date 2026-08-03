import { ref, onMounted, watch } from 'vue'

export function useTheme() {
  const isDark = ref(false)

  const toggleDark = () => {
    isDark.value = !isDark.value
  }

  onMounted(() => {
    // Check local storage or system preference
    if (localStorage.getItem('theme') === 'dark' || 
       (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      isDark.value = true
    }
  })

  watch(isDark, (val) => {
    if (val) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }, { immediate: true })

  return {
    isDark,
    toggleDark
  }
}
