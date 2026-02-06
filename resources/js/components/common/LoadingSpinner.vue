<script setup>
defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'text-blue-600'
  },
  text: {
    type: String,
    default: ''
  },
  fullScreen: {
    type: Boolean,
    default: false
  }
})

const sizeClasses = {
  sm: 'h-4 w-4',
  md: 'h-8 w-8',
  lg: 'h-12 w-12',
  xl: 'h-16 w-16'
}
</script>

<template>
  <div 
    :class="[
      'flex flex-col items-center justify-center',
      fullScreen ? 'fixed inset-0 z-50 bg-white/80 dark:bg-gray-900/80' : ''
    ]"
  >
    <svg 
      class="animate-spin" 
      :class="[sizeClasses[size], color]" 
      xmlns="http://www.w3.org/2000/svg" 
      fill="none" 
      viewBox="0 0 24 24"
    >
      <circle 
        class="opacity-25" 
        cx="12" 
        cy="12" 
        r="10" 
        stroke="currentColor" 
        stroke-width="4"
      ></circle>
      <path 
        class="opacity-75" 
        fill="currentColor" 
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      ></path>
    </svg>
    <span v-if="text" class="mt-2 text-sm text-gray-500 font-medium">{{ text }}</span>
  </div>
</template>
