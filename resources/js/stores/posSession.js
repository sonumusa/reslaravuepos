import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from './auth'

export const usePosSessionStore = defineStore('posSession', () => {
  // State
  const currentSession = ref(null)
  const sessions = ref([])
  const isLoading = ref(false)
  const error = ref(null)

  // Getters
  const hasActiveSession = computed(() => 
    currentSession.value?.status === 'open'
  )
  
  const sessionId = computed(() => 
    currentSession.value?.id || null
  )
  
  const terminalId = computed(() => 
    currentSession.value?.terminal_id || null
  )
  
  const openingCash = computed(() => 
    parseFloat(currentSession.value?.opening_cash || 0)
  )
  
  const totalSales = computed(() => 
    parseFloat(currentSession.value?.total_sales || 0)
  )
  
  const totalCashSales = computed(() => 
    parseFloat(currentSession.value?.total_cash_sales || 0)
  )
  
  const totalCardSales = computed(() => 
    parseFloat(currentSession.value?.total_card_sales || 0)
  )
  
  const expectedCash = computed(() => 
    openingCash.value + totalCashSales.value
  )

  // Actions
  async function checkActiveSession() {
    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) return null

    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/api/pos-sessions/active')
      if (response.data.success && response.data.data) {
        currentSession.value = response.data.data
        return currentSession.value
      }
      currentSession.value = null
      return null
    } catch (err) {
      if (err.response?.status !== 404) {
        error.value = err.response?.data?.message || err.message
        console.error('Check session error:', err)
      }
      currentSession.value = null
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function openSession(data) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post('/api/pos-sessions', {
        terminal_id: data.terminal_id,
        opening_cash: data.opening_cash,
        notes: data.notes || null
      })

      if (response.data.success) {
        currentSession.value = response.data.data
        return { success: true, data: currentSession.value }
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function closeSession(data) {
    if (!currentSession.value) {
      return { success: false, error: 'No active session' }
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await api.post(`/api/pos-sessions/${currentSession.value.id}/close`, {
        closing_cash: data.closing_cash,
        notes: data.notes || null
      })

      if (response.data.success) {
        const closedSession = response.data.data
        currentSession.value = null
        return { success: true, data: closedSession }
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function refreshSession() {
    if (!currentSession.value) return null

    try {
      const response = await api.get(`/api/pos-sessions/${currentSession.value.id}`)
      if (response.data.success) {
        currentSession.value = response.data.data
        return currentSession.value
      }
    } catch (err) {
      console.error('Refresh session error:', err)
    }
    return null
  }

  async function fetchSessions(params = {}) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/api/pos-sessions', { params })
      if (response.data.success) {
        sessions.value = response.data.data
        return sessions.value
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return []
    } finally {
      isLoading.value = false
    }
  }

  async function getSessionReport(sessionId) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/pos-sessions/${sessionId}/report`)
      if (response.data.success) {
        return { success: true, data: response.data.data }
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  function updateSessionStats(stats) {
    if (!currentSession.value) return

    currentSession.value = {
      ...currentSession.value,
      total_sales: stats.total_sales ?? currentSession.value.total_sales,
      total_cash_sales: stats.total_cash_sales ?? currentSession.value.total_cash_sales,
      total_card_sales: stats.total_card_sales ?? currentSession.value.total_card_sales,
      total_orders: stats.total_orders ?? currentSession.value.total_orders,
      total_tips: stats.total_tips ?? currentSession.value.total_tips
    }
  }

  function clearSession() {
    currentSession.value = null
    error.value = null
  }

  return {
    // State
    currentSession,
    sessions,
    isLoading,
    error,
    
    // Getters
    hasActiveSession,
    sessionId,
    terminalId,
    openingCash,
    totalSales,
    totalCashSales,
    totalCardSales,
    expectedCash,
    
    // Actions
    checkActiveSession,
    openSession,
    closeSession,
    refreshSession,
    fetchSessions,
    getSessionReport,
    updateSessionStats,
    clearSession
  }
}, {
  persist: {
    key: 'posSession',
    paths: ['currentSession'],
    storage: localStorage
  }
})
