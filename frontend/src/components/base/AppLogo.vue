<template>
  <div :class="['inline-flex items-center gap-3 select-none', containerClasses]">
    <!-- EH Speech Bubble SVG Logo Mark -->
    <div :class="['relative flex-shrink-0 flex items-center justify-center filter drop-shadow-[0_4px_16px_rgba(168,85,247,0.4)]', iconClasses]">
      <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
        <!-- Definitions for Gradients & Shadows -->
        <defs>
          <!-- Speech Bubble Outer Radial Glow / Gradient -->
          <linearGradient id="bubbleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#c084fc" />
            <stop offset="45%" stop-color="#9333ea" />
            <stop offset="100%" stop-color="#6b21a8" />
          </linearGradient>

          <linearGradient id="bubbleHighlight" x1="20%" y1="0%" x2="80%" y2="50%">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6" />
            <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
          </linearGradient>

          <!-- EH Metallic Monogram Gradient -->
          <linearGradient id="ehMetallic" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#ffffff" />
            <stop offset="50%" stop-color="#e2e8f0" />
            <stop offset="100%" stop-color="#cbd5e1" />
          </linearGradient>

          <filter id="ehShadow" x="-10%" y="-10%" width="130%" height="130%">
            <feDropShadow dx="1" dy="2" stdDeviation="1.5" flood-color="#000000" flood-opacity="0.4" />
          </filter>
        </defs>

        <!-- Outer Purple Speech Bubble Body -->
        <path
          d="M 50 10 
             C 73 10, 90 26, 90 47 
             C 90 68, 73 84, 50 84 
             C 42 84, 35 82, 28 78 
             L 12 88 
             L 17 73 
             C 12 66, 10 57, 10 47 
             C 10 26, 27 10, 50 10 Z"
          fill="url(#bubbleGrad)"
        />

        <!-- Top Highlight Rim -->
        <path
          d="M 50 12 
             C 70 12, 86 26, 88 45 
             C 80 28, 65 16, 45 16 
             C 28 16, 16 26, 13 40 
             C 16 24, 31 12, 50 12 Z"
          fill="url(#bubbleHighlight)"
        />

        <!-- Stylized "EH" Metallic Monogram Inner Graphic -->
        <!-- Letter E (Swirling curve) -->
        <path
          d="M 28 36 
             C 32 30, 48 30, 52 36 
             L 36 36 
             C 33 36, 32 38, 32 41 
             L 48 41 
             C 50 41, 51 43, 49 46 
             L 33 46 
             C 32 48, 33 52, 36 52 
             L 54 52 
             C 50 60, 32 60, 27 52 
             C 24 45, 24 40, 28 36 Z"
          fill="url(#ehMetallic)"
          filter="url(#ehShadow)"
        />

        <!-- Letter H (Interlocked slanted futuristic H) -->
        <path
          d="M 50 32 
             L 57 32 
             L 53 44 
             L 66 44 
             L 70 32 
             L 77 32 
             L 66 62 
             L 59 62 
             L 63 50 
             L 50 50 
             L 46 62 
             L 39 62 
             Z"
          fill="url(#ehMetallic)"
          filter="url(#ehShadow)"
        />
      </svg>
    </div>

    <!-- Brand Name & Tagline Text -->
    <div v-if="!iconOnly" class="flex flex-col">
      <span :class="['font-bold tracking-tight text-white font-sans leading-none', textClasses]">
        Esmiring<span class="text-purple-200">HOY</span>
      </span>
      <span v-if="showTagline" :class="['font-semibold tracking-[0.2em] text-purple-300/80 uppercase font-sans mt-1', taglineClasses]">
        CONNECT &amp; CHAT
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v)
  },
  iconOnly: {
    type: Boolean,
    default: false
  },
  showTagline: {
    type: Boolean,
    default: true
  }
})

const containerClasses = computed(() => {
  return props.iconOnly ? 'justify-center' : ''
})

const iconClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-8 h-8'
    case 'lg': return 'w-12 h-12'
    case 'xl': return 'w-16 h-16'
    default: return 'w-10 h-10'
  }
})

const textClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'text-lg'
    case 'lg': return 'text-2xl'
    case 'xl': return 'text-3xl'
    default: return 'text-xl'
  }
})

const taglineClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'text-[9px]'
    case 'lg': return 'text-[11px]'
    case 'xl': return 'text-xs'
    default: return 'text-[10px]'
  }
})
</script>
