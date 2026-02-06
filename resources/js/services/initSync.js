import { syncService } from './syncService'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'

export async function initializeSyncService() {
  console.log('[InitSync] Initializing sync service...')

  // Initialize the app store
  const appStore = useAppStore()
  appStore.initApp()

  // Initialize sync service
  syncService.init()

  // If user is authenticated, check for pending syncs
  const authStore = useAuthStore()
  if (authStore.isAuthenticated && navigator.onLine) {
    // Wait for app to be fully loaded
    setTimeout(async () => {
      await syncService.syncPendingData()
    }, 2000)
  }

  console.log('[InitSync] Sync service initialized')
}

export function destroySyncService() {
  syncService.destroy()
}
