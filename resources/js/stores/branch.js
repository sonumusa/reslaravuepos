import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from './auth'

export const useBranchStore = defineStore('branch', () => {
  // State
  const branches = ref([])
  const currentBranch = ref(null)
  const isLoading = ref(false)
  const error = ref(null)

  // Getters
  const activeBranches = computed(() => 
    branches.value.filter(b => b.is_active)
  )
  
  const branchById = computed(() => (id) => 
    branches.value.find(b => b.id === id)
  )

  const currentBranchId = computed(() => 
    currentBranch.value?.id || null
  )

  const gstRate = computed(() => 
    currentBranch.value?.gst_rate || 16
  )

  const branchSettings = computed(() => 
    currentBranch.value?.settings || {}
  )

  // Actions
  async function fetchBranches() {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/api/branches')
      if (response.data.success) {
        branches.value = response.data.data
        return branches.value
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Fetch branches error:', err)
      return []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchBranch(id) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/branches/${id}`)
      if (response.data.success) {
        const branchData = response.data.data
        
        // Update in branches array
        const index = branches.value.findIndex(b => b.id === id)
        if (index !== -1) {
          branches.value[index] = branchData
        } else {
          branches.value.push(branchData)
        }

        return branchData
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Fetch branch error:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function createBranch(data) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post('/api/branches', data)
      if (response.data.success) {
        branches.value.push(response.data.data)
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

  async function updateBranch(id, data) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.put(`/api/branches/${id}`, data)
      if (response.data.success) {
        const index = branches.value.findIndex(b => b.id === id)
        if (index !== -1) {
          branches.value[index] = response.data.data
        }
        
        // Update current branch if it's the one being edited
        if (currentBranch.value?.id === id) {
          currentBranch.value = response.data.data
        }
        
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

  async function deleteBranch(id) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.delete(`/api/branches/${id}`)
      if (response.data.success) {
        branches.value = branches.value.filter(b => b.id !== id)
        return { success: true }
      }
      throw new Error(response.data.message)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  function setCurrentBranch(branch) {
    currentBranch.value = branch
    
    // Also update in auth store
    const authStore = useAuthStore()
    authStore.setBranch(branch)
  }

  async function fetchCurrentBranchDetails() {
    const authStore = useAuthStore()
    const branchId = authStore.branchId

    if (!branchId) return null

    const branchData = await fetchBranch(branchId)
    if (branchData) {
      currentBranch.value = branchData
    }
    return branchData
  }

  function getBranchSetting(key, defaultValue = null) {
    return branchSettings.value[key] ?? defaultValue
  }

  return {
    // State
    branches,
    currentBranch,
    isLoading,
    error,
    
    // Getters
    activeBranches,
    branchById,
    currentBranchId,
    gstRate,
    branchSettings,
    
    // Actions
    fetchBranches,
    fetchBranch,
    createBranch,
    updateBranch,
    deleteBranch,
    setCurrentBranch,
    fetchCurrentBranchDetails,
    getBranchSetting
  }
}, {
  persist: {
    key: 'branch',
    paths: ['currentBranch'],
    storage: localStorage
  }
})
