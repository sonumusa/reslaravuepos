import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAppStore = defineStore('app', () => {
  // State
  const isOnline = ref(navigator.onLine)
  const isSyncing = ref(false)
  const syncQueue = ref([])
  const lastSyncTime = ref(null)
  const notifications = ref([])
  const sidebarOpen = ref(true)
  const theme = ref('light')
  const locale = ref('en')
  const isLoading = ref(false)
  const loadingMessage = ref('')
  const pendingOfflineActions = ref(0)
  const appVersion = ref('1.0.0')

  // Getters
  const hasOfflineData = computed(() => pendingOfflineActions.value > 0)
  
  const hasPendingSync = computed(() => syncQueue.value.length > 0)
  
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.read)
  )
  
  const unreadCount = computed(() => unreadNotifications.value.length)

  // Actions
  function setOnlineStatus(status) {
    isOnline.value = status
  }

  function setSyncing(status) {
    isSyncing.value = status
  }

  function updateLastSyncTime() {
    lastSyncTime.value = new Date().toISOString()
  }

  function addToSyncQueue(item) {
    syncQueue.value.push({
      id: Date.now(),
      ...item,
      createdAt: new Date().toISOString()
    })
  }

  function removeFromSyncQueue(id) {
    syncQueue.value = syncQueue.value.filter(item => item.id !== id)
  }

  function clearSyncQueue() {
    syncQueue.value = []
  }

  function addNotification(notification) {
    const id = Date.now()
    notifications.value.unshift({
      id,
      read: false,
      createdAt: new Date().toISOString(),
      ...notification
    })
    
    // Keep only last 50 notifications
    if (notifications.value.length > 50) {
      notifications.value = notifications.value.slice(0, 50)
    }
    
    return id
  }

  function markNotificationRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
    }
  }

  function markAllNotificationsRead() {
    notifications.value.forEach(n => n.read = true)
  }

  function removeNotification(id) {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  function clearNotifications() {
    notifications.value = []
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebarOpen(open) {
    sidebarOpen.value = open
  }

  function setTheme(newTheme) {
    theme.value = newTheme
    document.documentElement.classList.toggle('dark', newTheme === 'dark')
    localStorage.setItem('theme', newTheme)
  }

  function setLocale(newLocale) {
    locale.value = newLocale
    localStorage.setItem('locale', newLocale)
  }

  function setLoading(status, message = '') {
    isLoading.value = status
    loadingMessage.value = message
  }

  function incrementPendingOffline() {
    pendingOfflineActions.value++
  }

  function decrementPendingOffline() {
    if (pendingOfflineActions.value > 0) {
      pendingOfflineActions.value--
    }
  }

  function setPendingOfflineCount(count) {
    pendingOfflineActions.value = count
  }

  // Toast/Alert helpers
  function showSuccess(message, duration = 3000) {
    return addNotification({
      type: 'success',
      message,
      duration,
      autoClose: true
    })
  }

  function showError(message, duration = 5000) {
    return addNotification({
      type: 'error',
      message,
      duration,
      autoClose: true
    })
  }

  function showWarning(message, duration = 4000) {
    return addNotification({
      type: 'warning',
      message,
      duration,
      autoClose: true
    })
  }

  function showInfo(message, duration = 3000) {
    return addNotification({
      type: 'info',
      message,
      duration,
      autoClose: true
    })
  }

  // Initialize
  function initApp() {
    // Set up online/offline listeners
    window.addEventListener('online', () => setOnlineStatus(true))
    window.addEventListener('offline', () => setOnlineStatus(false))
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light'
    setTheme(savedTheme)
    
    // Load saved locale
    const savedLocale = localStorage.getItem('locale') || 'en'
    setLocale(savedLocale)
  }

  return {
    // State
    isOnline,
    isSyncing,
    syncQueue,
    lastSyncTime,
    notifications,
    sidebarOpen,
    theme,
    locale,
    isLoading,
    loadingMessage,
    pendingOfflineActions,
    appVersion,
    
    // Getters
    hasOfflineData,
    hasPendingSync,
    unreadNotifications,
    unreadCount,
    
    // Actions
    setOnlineStatus,
    setSyncing,
    updateLastSyncTime,
    addToSyncQueue,
    removeFromSyncQueue,
    clearSyncQueue,
    addNotification,
    markNotificationRead,
    markAllNotificationsRead,
    removeNotification,
    clearNotifications,
    toggleSidebar,
    setSidebarOpen,
    setTheme,
    setLocale,
    setLoading,
    incrementPendingOffline,
    decrementPendingOffline,
    setPendingOfflineCount,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    initApp
  }
}, {
  persist: {
    key: 'app',
    paths: ['theme', 'locale', 'sidebarOpen', 'syncQueue', 'lastSyncTime'],
    storage: localStorage
  }
})
