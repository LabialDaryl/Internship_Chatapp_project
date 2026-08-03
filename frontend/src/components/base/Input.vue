<template>
  <div class="flex flex-col gap-1 w-full">
    <label v-if="label" :for="id" class="text-sm font-medium text-slate-700 dark:text-slate-300">
      {{ label }}
    </label>
    <div class="relative">
      <div v-if="$slots.icon" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-purple-300/60">
        <slot name="icon"></slot>
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="[
          variant === 'pill-purple' ? 'input-pill-purple' : 'input-base',
          $slots.icon ? 'pl-11' : '',
          $slots.suffix ? 'pr-11' : '',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''
        ]"
        v-bind="$attrs"
      />
      <div v-if="$slots.suffix" class="absolute inset-y-0 right-0 pr-4 flex items-center text-purple-300/60">
        <slot name="suffix"></slot>
      </div>
    </div>
    <span v-if="error" class="text-xs text-red-400 mt-1 pl-3">{{ error }}</span>
  </div>
</template>

<script setup>
import { useId } from 'vue'

defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'base'
  }
})

defineEmits(['update:modelValue'])

const id = useId()
</script>
