<template>
  <div class="relative inline-block">
    <img
      v-if="src"
      :src="src"
      :alt="alt"
      :class="['rounded-full object-cover bg-slate-200 dark:bg-slate-700', sizeClasses[size]]"
    />
    <div
      v-else
      :class="['rounded-full flex items-center justify-center bg-gradient-to-br from-primary-400 to-secondary-500 text-white font-bold', sizeClasses[size]]"
    >
      {{ initials }}
    </div>
    
    <span
      v-if="showStatus"
      :class="[
        'absolute bottom-0 right-0 block rounded-full ring-2 ring-white dark:ring-slate-900',
        isOnline ? 'bg-emerald-500' : 'bg-slate-400',
        statusSizeClasses[size]
      ]"
    ></span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  src: {
    type: String,
    default: ''
  },
  alt: {
    type: String,
    default: 'Avatar'
  },
  name: {
    type: String,
    default: 'User'
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v)
  },
  showStatus: {
    type: Boolean,
    default: false
  },
  isOnline: {
    type: Boolean,
    default: false
  }
})

const sizeClasses = {
  xs: 'w-6 h-6 text-xs',
  sm: 'w-8 h-8 text-sm',
  md: 'w-10 h-10 text-base',
  lg: 'w-12 h-12 text-lg',
  xl: 'w-16 h-16 text-xl'
}

const statusSizeClasses = {
  xs: 'w-1.5 h-1.5',
  sm: 'w-2 h-2',
  md: 'w-2.5 h-2.5',
  lg: 'w-3 h-3',
  xl: 'w-4 h-4'
}

const initials = computed(() => {
  if (!props.name) return '?'
  const parts = props.name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return props.name.substring(0, 2).toUpperCase()
})
</script>
