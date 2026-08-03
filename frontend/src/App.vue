<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useTheme } from './composables/useTheme'

useTheme()
</script>
