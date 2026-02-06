<template>
  <div v-if="showInstallPrompt" class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white rounded-lg shadow-xl p-4 border border-blue-100 z-50">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <h3 class="font-bold text-gray-900">Install App</h3>
        <p class="text-sm text-gray-600 mt-1">Install our app for a better experience with offline access.</p>
      </div>
      <button @click="dismissPrompt" class="text-gray-400 hover:text-gray-500">
        <span class="sr-only">Close</span>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="mt-4 flex gap-3">
      <button @click="installApp" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
        Install
      </button>
      <button @click="dismissPrompt" class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
        Not Now
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const deferredPrompt = ref(null)
const showInstallPrompt = ref(false)

onMounted(() => {
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt.value = e
    showInstallPrompt.value = true
  })

  window.addEventListener('appinstalled', () => {
    showInstallPrompt.value = false
    deferredPrompt.value = null
  })
})

async function installApp() {
  if (!deferredPrompt.value) return
  
  deferredPrompt.value.prompt()
  const { outcome } = await deferredPrompt.value.userChoice
  
  if (outcome === 'accepted') {
    deferredPrompt.value = null
    showInstallPrompt.value = false
  }
}

function dismissPrompt() {
  showInstallPrompt.value = false
}
</script>
