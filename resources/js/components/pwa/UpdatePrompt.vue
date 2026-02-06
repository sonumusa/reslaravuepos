<template>
  <div v-if="needRefresh" class="fixed bottom-4 right-4 bg-gray-900 text-white rounded-lg shadow-xl p-4 flex items-center gap-4 z-50 max-w-sm">
    <div class="flex-1">
      <p class="text-sm font-medium">New content available</p>
    </div>
    <div class="flex gap-2">
      <button @click="updateServiceWorker()" class="text-blue-400 hover:text-blue-300 text-sm font-bold">
        Reload
      </button>
      <button @click="close" class="text-gray-400 hover:text-gray-300">
        <span class="sr-only">Close</span>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRegisterSW } from 'virtual:pwa-register/vue'

const { needRefresh, updateServiceWorker } = useRegisterSW()

function close() {
  needRefresh.value = false
}
</script>
