import { ref, computed, onMounted, onUnmounted } from 'vue'
import { syncService } from '@/services/syncService'
import { useAppStore } from '@/stores/app'

export function useSync() {
  const appStore = useAppStore()
  
  const isSyncing = ref(false)
  const pendingCount = ref(0)
  const failedCount = ref(0)
  const lastSyncTime = ref(null)
  
  const isOnline = computed(() => appStore.isOnline)
  const hasPendingSync = computed(() => pendingCount.value > 0)
  const hasFailedSync = computed(() => failedCount.value > 0)

  function updateStatus() {
    const status = syncService.getSyncStatus()
    isSyncing.value = status.isSyncing
    pendingCount.value = status.pendingCount
    failedCount.value = status.failedCount
    lastSyncTime.value = status.lastSync
  }

  async function syncNow() {
    if (!isOnline.value || isSyncing.value) {
      return { synced: 0, failed: 0 }
    }
    
    isSyncing.value = true
    try {
      const result = await syncService.syncPendingData()
      updateStatus()
      return result
    } finally {
      isSyncing.value = false
    }
  }

  async function retryFailed() {
    if (!isOnline.value || isSyncing.value) {
      return { synced: 0, failed: 0 }
    }
    
    isSyncing.value = true
    try {
      const result = await syncService.retryFailedItems()
      updateStatus()
      return result
    } finally {
      isSyncing.value = false
    }
  }

  async function downloadOfflineData() {
    return await syncService.downloadForOffline()
  }

  async function clearOfflineData() {
    const confirmed = confirm('This will delete all offline data. Are you sure?')
    if (confirmed) {
      const result = await syncService.clearOfflineData()
      updateStatus()
      return result
    }
    return false
  }

  function addToSyncQueue(type, action, data) {
    return syncService.addToQueue({ type, action, data })
  }

  // Listener for sync events
  let unsubscribe = null

  onMounted(() => {
    updateStatus()
    
    unsubscribe = syncService.addListener((event, data) => {
      if (event === 'sync-complete') {
        updateStatus()
      } else if (event === 'online' || event === 'offline') {
        updateStatus()
      }
    })
  })

  onUnmounted(() => {
    if (unsubscribe) {
      unsubscribe()
    }
  })

  return {
    // State
    isSyncing,
    pendingCount,
    failedCount,
    lastSyncTime,
    
    // Computed
    isOnline,
    hasPendingSync,
    hasFailedSync,
    
    // Methods
    syncNow,
    retryFailed,
    downloadOfflineData,
    clearOfflineData,
    addToSyncQueue,
    updateStatus
  }
}
