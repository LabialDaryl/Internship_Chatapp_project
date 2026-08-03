import { ref, onMounted, onUnmounted } from 'vue'

export function useBreakpoints() {
  const windowWidth = ref(window.innerWidth)

  const onWidthChange = () => {
    windowWidth.value = window.innerWidth
  }

  onMounted(() => {
    window.addEventListener('resize', onWidthChange)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', onWidthChange)
  })

  // Tailwind Default Breakpoints
  // sm: 640px, md: 768px, lg: 1024px, xl: 1280px

  return {
    isMobile: () => windowWidth.value < 768,
    isTablet: () => windowWidth.value >= 768 && windowWidth.value < 1024,
    isDesktop: () => windowWidth.value >= 1024,
    windowWidth
  }
}
