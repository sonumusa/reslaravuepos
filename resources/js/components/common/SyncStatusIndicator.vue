<template>
  <div class="sync-status flex items-center gap-2">
    <!-- Online/Offline Status -->
    <div class="flex items-center gap-1">
      <span 
        class="w-2 h-2 rounded-full" 
        :class="isOnline ? 'bg-green-500' : 'bg-red-500'" 
      ></span>
      <span class="text-xs text-gray-500">
        {{ isOnline ? 'Online' : 'Offline' }}
      </span>
    </div>

    <!-- Syncing Indicator -->
    <div v-if="isSyncing" class="flex items-center gap-1 text-blue-500">
      <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
      <span class="text-xs">Syncing...</span>
    </div>

    <!-- Pending Count -->
    <div 
      v-else-if="hasPendingSync" 
      class="flex items-center gap-1 text-amber-500 cursor-pointer" 
      @click="handleSync" 
      title="Click to sync" 
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
      <span class="text-xs">{{ pendingCount }} pending</span>
    </div>

    <!-- Failed Count -->
    <div 
      v-if="hasFailedSync" 
      class="flex items-center gap-1 text-red-500 cursor-pointer" 
      @click="handleRetryFailed" 
      title="Click to retry" 
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <span class="text-xs">{{ failedCount }} failed</span>
    </div>

    <!-- Last Sync Time -->
    <div v-if="lastSyncTime && !isSyncing" class="text-xs text-gray-400 hidden md:block">
      Last sync: {{ formatTime(lastSyncTime) }}
    </div>
  </div>
</template>

<script setup>
import { useSync } from '@/composables/useSync'

const { 
  isSyncing, 
  isOnline, 
  pendingCount, 
  failedCount, 
  hasPendingSync, 
  hasFailedSync, 
  lastSyncTime, 
  syncNow, 
  retryFailed 
} = useSync()

async function handleSync() {
  await syncNow()
}

async function handleRetryFailed() {
  await retryFailed()
}

function formatTime(timestamp) {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}
</script>
