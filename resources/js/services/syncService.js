import { useOfflineDb } from './offline-db'
import api from './api'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import { useOrderStore } from '@/stores/order'
import { useMenuStore } from '@/stores/menu'
import { useTableStore } from '@/stores/table'
import { useCustomerStore } from '@/stores/customer'

class SyncService {
  constructor() {
    this.isSyncing = false
    this.syncQueue = []
    this.retryAttempts = 3
    this.retryDelay = 1000
    this.syncInterval = null
    this.listeners = new Set()
  }

  // Initialize sync service
  init() {
    // Listen for online/offline events
    window.addEventListener('online', () => this.handleOnline())
    window.addEventListener('offline', () => this.handleOffline())

    // Start periodic sync check
    this.startPeriodicSync()

    // Load pending sync items from localStorage
    this.loadSyncQueue()

    console.log('[SyncService] Initialized')
  }

  // Start periodic sync (every 30 seconds when online)
  startPeriodicSync(intervalMs = 30000) {
    if (this.syncInterval) {
      clearInterval(this.syncInterval)
    }

    this.syncInterval = setInterval(() => {
      if (navigator.onLine && !this.isSyncing) {
        this.syncPendingData()
      }
    }, intervalMs)
  }

  // Stop periodic sync
  stopPeriodicSync() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval)
      this.syncInterval = null
    }
  }

  // Handle coming online
  async handleOnline() {
    console.log('[SyncService] Online - starting sync')
    const appStore = useAppStore()
    appStore.setOnlineStatus(true)

    // Wait a moment for connection to stabilize
    await this.delay(1000)

    // Sync all pending data
    await this.syncPendingData()

    // Refresh data from server
    await this.refreshFromServer()

    this.notifyListeners('online')
  }

  // Handle going offline
  handleOffline() {
    console.log('[SyncService] Offline')
    const appStore = useAppStore()
    appStore.setOnlineStatus(false)
    this.notifyListeners('offline')
  }

  // Add item to sync queue
  async addToQueue(item) {
    const syncItem = {
      id: Date.now().toString(),
      timestamp: new Date().toISOString(),
      attempts: 0,
      ...item
    }

    this.syncQueue.push(syncItem)
    await this.saveSyncQueue()

    const appStore = useAppStore()
    appStore.incrementPendingOffline()
    appStore.addToSyncQueue(syncItem)

    console.log('[SyncService] Added to queue:', syncItem.type, syncItem.action)

    // Try to sync immediately if online
    if (navigator.onLine) {
      this.syncPendingData()
    }

    return syncItem.id
  }

  // Remove item from sync queue
  async removeFromQueue(id) {
    this.syncQueue = this.syncQueue.filter(item => item.id !== id)
    await this.saveSyncQueue()

    const appStore = useAppStore()
    appStore.decrementPendingOffline()
    appStore.removeFromSyncQueue(id)
  }

  // Save sync queue to localStorage
  async saveSyncQueue() {
    try {
      localStorage.setItem('sync_queue', JSON.stringify(this.syncQueue))
    } catch (err) {
      console.error('[SyncService] Failed to save queue:', err)
    }
  }

  // Load sync queue from localStorage
  loadSyncQueue() {
    try {
      const stored = localStorage.getItem('sync_queue')
      if (stored) {
        this.syncQueue = JSON.parse(stored)
        const appStore = useAppStore()
        appStore.setPendingOfflineCount(this.syncQueue.length)
      }
    } catch (err) {
      console.error('[SyncService] Failed to load queue:', err)
      this.syncQueue = []
    }
  }

  // Main sync function
  async syncPendingData() {
    if (this.isSyncing || !navigator.onLine) {
      return { synced: 0, failed: 0 }
    }

    this.isSyncing = true
    const appStore = useAppStore()
    appStore.setSyncing(true)

    console.log('[SyncService] Starting sync, queue length:', this.syncQueue.length)

    let synced = 0
    let failed = 0

    // Process queue in order
    const itemsToSync = [...this.syncQueue]

    for (const item of itemsToSync) {
      try {
        await this.syncItem(item)
        await this.removeFromQueue(item.id)
        synced++
      } catch (err) {
        console.error('[SyncService] Sync item failed:', err)
        item.attempts++
        item.lastError = err.message

        if (item.attempts >= this.retryAttempts) {
          // Move to failed items
          await this.handleFailedSync(item)
          await this.removeFromQueue(item.id)
        }
        failed++
      }
    }

    // Sync offline orders from IndexedDB
    const offlineOrdersResult = await this.syncOfflineOrders()
    synced += offlineOrdersResult.synced
    failed += offlineOrdersResult.failed

    this.isSyncing = false
    appStore.setSyncing(false)
    appStore.updateLastSyncTime()

    console.log('[SyncService] Sync complete. Synced:', synced, 'Failed:', failed)

    this.notifyListeners('sync-complete', { synced, failed })

    return { synced, failed }
  }

  // Sync individual item based on type
  async syncItem(item) {
    switch (item.type) {
      case 'order':
        return await this.syncOrder(item)
      case 'invoice':
        return await this.syncInvoice(item)
      case 'payment':
        return await this.syncPayment(item)
      case 'customer':
        return await this.syncCustomer(item)
      case 'table_status':
        return await this.syncTableStatus(item)
      default:
        console.warn('[SyncService] Unknown sync type:', item.type)
        return null
    }
  }

  // Sync order
  async syncOrder(item) {
    const { action, data } = item
    const offlineDb = useOfflineDb()

    switch (action) {
      case 'create': {
        const response = await api.post('/api/orders', data)
        if (response.data.success) {
          // Update local order with server data
          await offlineDb.orders.where('uuid').equals(data.uuid).modify({
            id: response.data.data.id,
            order_number: response.data.data.order_number,
            synced: true,
            synced_at: new Date().toISOString()
          })
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to create order')
      }

      case 'update': {
        const response = await api.put(`/api/orders/${data.id}`, data)
        if (response.data.success) {
          await offlineDb.orders.update(data.id, { synced: true })
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to update order')
      }

      case 'status': {
        const response = await api.patch(`/api/orders/${data.id}/status`, {
          status: data.status
        })
        if (response.data.success) {
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to update status')
      }

      default:
        throw new Error(`Unknown order action: ${action}`)
    }
  }

  // Sync invoice
  async syncInvoice(item) {
    const { action, data } = item
    const offlineDb = useOfflineDb()

    switch (action) {
      case 'create': {
        const response = await api.post('/api/invoices', data)
        if (response.data.success) {
          await offlineDb.invoices.where('uuid').equals(data.uuid).modify({
            id: response.data.data.id,
            invoice_number: response.data.data.invoice_number,
            synced: true
          })
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to create invoice')
      }

      default:
        throw new Error(`Unknown invoice action: ${action}`)
    }
  }

  // Sync payment
  async syncPayment(item) {
    const { action, data } = item
    const offlineDb = useOfflineDb()

    switch (action) {
      case 'create': {
        const response = await api.post('/api/payments', data)
        if (response.data.success) {
          await offlineDb.payments.where('uuid').equals(data.uuid).modify({
            id: response.data.data.id,
            synced: true
          })
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to create payment')
      }

      default:
        throw new Error(`Unknown payment action: ${action}`)
    }
  }

  // Sync customer
  async syncCustomer(item) {
    const { action, data } = item

    switch (action) {
      case 'create': {
        const response = await api.post('/api/customers', data)
        if (response.data.success) {
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to create customer')
      }

      case 'update': {
        const response = await api.put(`/api/customers/${data.id}`, data)
        if (response.data.success) {
          return response.data.data
        }
        throw new Error(response.data.message || 'Failed to update customer')
      }

      default:
        throw new Error(`Unknown customer action: ${action}`)
    }
  }

  // Sync table status
  async syncTableStatus(item) {
    const { data } = item

    const response = await api.patch(`/api/tables/${data.table_id}/status`, {
      status: data.status
    })

    if (response.data.success) {
      return response.data.data
    }
    throw new Error(response.data.message || 'Failed to update table status')
  }

  // Sync offline orders from IndexedDB
  async syncOfflineOrders() {
    let synced = 0
    let failed = 0
    const offlineDb = useOfflineDb()

    try {
      // Get all unsynced orders
      const unsyncedOrders = await offlineDb.orders
        .filter(order => order.created_offline && !order.synced)
        .toArray()

      for (const order of unsyncedOrders) {
        try {
          const response = await api.post('/api/orders', order)
          
          if (response.data.success) {
            await offlineDb.orders.update(order.uuid, {
              id: response.data.data.id,
              order_number: response.data.data.order_number,
              synced: true,
              synced_at: new Date().toISOString()
            })
            synced++
          }
        } catch (err) {
          console.error('[SyncService] Failed to sync order:', order.uuid, err)
          failed++
        }
      }
    } catch (err) {
      console.error('[SyncService] Error getting unsynced orders:', err)
    }

    return { synced, failed }
  }

  // Refresh data from server
  async refreshFromServer() {
    const authStore = useAuthStore()
    
    if (!authStore.isAuthenticated) {
      return
    }

    console.log('[SyncService] Refreshing data from server')

    try {
      // Refresh stores in parallel
      await Promise.allSettled([
        useMenuStore().fetchAllMenu(true),
        useTableStore().fetchTables(true),
        useOrderStore().fetchOrders({ status: ['open', 'preparing', 'ready'] }),
      ])
    } catch (err) {
      console.error('[SyncService] Refresh from server error:', err)
    }
  }

  // Handle failed sync item
  async handleFailedSync(item) {
    console.error('[SyncService] Item failed after max retries:', item)

    // Store in failed items for manual retry
    try {
      const failedItems = JSON.parse(localStorage.getItem('sync_failed') || '[]')
      failedItems.push({
        ...item,
        failedAt: new Date().toISOString()
      })
      localStorage.setItem('sync_failed', JSON.stringify(failedItems))
    } catch (err) {
      console.error('[SyncService] Failed to store failed item:', err)
    }

    // Notify user
    const appStore = useAppStore()
    appStore.showError(`Failed to sync ${item.type}: ${item.lastError}`)
  }

  // Retry failed items
  async retryFailedItems() {
    try {
      const failedItems = JSON.parse(localStorage.getItem('sync_failed') || '[]')
      
      if (failedItems.length === 0) {
        return { synced: 0, failed: 0 }
      }

      let synced = 0
      let failed = 0

      for (const item of failedItems) {
        item.attempts = 0 // Reset attempts
        try {
          await this.syncItem(item)
          synced++
        } catch (err) {
          failed++
        }
      }

      // Clear failed items that succeeded
      const remainingFailed = failedItems.slice(synced)
      localStorage.setItem('sync_failed', JSON.stringify(remainingFailed))

      return { synced, failed }
    } catch (err) {
      console.error('[SyncService] Retry failed items error:', err)
      return { synced: 0, failed: 0 }
    }
  }

  // Get failed items count
  getFailedItemsCount() {
    try {
      const failedItems = JSON.parse(localStorage.getItem('sync_failed') || '[]')
      return failedItems.length
    } catch {
      return 0
    }
  }

  // Clear all failed items
  clearFailedItems() {
    localStorage.removeItem('sync_failed')
  }

  // Download all data for offline use
  async downloadForOffline() {
    const appStore = useAppStore()
    appStore.setLoading(true, 'Downloading data for offline use...')

    try {
      // Download all menu data
      const menuStore = useMenuStore()
      await menuStore.fetchAllMenu(true)

      // Download tables
      const tableStore = useTableStore()
      await tableStore.fetchTables(true)

      // Download customers
      const customerStore = useCustomerStore()
      await customerStore.fetchCustomers({ limit: 1000 })

      appStore.showSuccess('Data downloaded for offline use')
      return true
    } catch (err) {
      console.error('[SyncService] Download for offline error:', err)
      appStore.showError('Failed to download data for offline use')
      return false
    } finally {
      appStore.setLoading(false)
    }
  }

  // Clear all offline data
  async clearOfflineData() {
    const offlineDb = useOfflineDb()
    try {
      await offlineDb.delete()
      await offlineDb.open()
      this.syncQueue = []
      localStorage.removeItem('sync_queue')
      localStorage.removeItem('sync_failed')
      
      const appStore = useAppStore()
      appStore.setPendingOfflineCount(0)
      appStore.clearSyncQueue()

      console.log('[SyncService] Offline data cleared')
      return true
    } catch (err) {
      console.error('[SyncService] Clear offline data error:', err)
      return false
    }
  }

  // Get sync status
  getSyncStatus() {
    return {
      isSyncing: this.isSyncing,
      pendingCount: this.syncQueue.length,
      failedCount: this.getFailedItemsCount(),
      isOnline: navigator.onLine,
      lastSync: localStorage.getItem('last_sync_time')
    }
  }

  // Add event listener
  addListener(callback) {
    this.listeners.add(callback)
    return () => this.listeners.delete(callback)
  }

  // Notify all listeners
  notifyListeners(event, data = null) {
    this.listeners.forEach(callback => {
      try {
        callback(event, data)
      } catch (err) {
        console.error('[SyncService] Listener error:', err)
      }
    })
  }

  // Utility: delay
  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  // Destroy service
  destroy() {
    this.stopPeriodicSync()
    window.removeEventListener('online', this.handleOnline)
    window.removeEventListener('offline', this.handleOffline)
    this.listeners.clear()
  }
}

// Create singleton instance
export const syncService = new SyncService()

// Export for use in Vue
export default syncService
