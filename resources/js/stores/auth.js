import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(null)
  const branch = ref(null)
  const isLoading = ref(false)
  const error = ref(null)
  const permissions = ref([])

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => user.value?.role || null)
  const isSuperAdmin = computed(() => userRole.value === 'superadmin')
  const isAdmin = computed(() => ['superadmin', 'admin'].includes(userRole.value))
  const isCashier = computed(() => userRole.value === 'cashier')
  const isWaiter = computed(() => userRole.value === 'waiter')
  const isKitchen = computed(() => userRole.value === 'kitchen')
  const branchId = computed(() => branch.value?.id || user.value?.branch_id)
  const userName = computed(() => user.value?.name || 'Guest')
  const userInitials = computed(() => {
    if (!user.value?.name) return '?'
    return user.value.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

  // Actions
  async function login(credentials) {
    isLoading.value = true
    error.value = null

    try {
      // Get CSRF cookie first (for SPA authentication)
      try {
        await api.get('/sanctum/csrf-cookie', {
            baseURL: 'http://localhost:8000'
        })
      } catch (e) {
        console.warn('CSRF cookie failed, continuing with token auth', e)
      }

      // Attempt login
      const response = await api.post('/auth/login', {
        email: credentials.email,
        password: credentials.password,
        device_name: credentials.device_name || 'pos_terminal'
      })

      if (response.data.success) {
        token.value = response.data.data.token
        user.value = response.data.data.user
        branch.value = response.data.data.branch || null
        permissions.value = response.data.data.permissions || []

        // Set token in API headers
        api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

        // Store in localStorage for persistence
        localStorage.setItem('auth_token', token.value)

        return { success: true }
      } else {
        throw new Error(response.data.message || 'Login failed')
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Login failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function loginWithPin(pin, terminalId = null) {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.post('/auth/login-pin', {
        pin: pin,
        terminal_id: terminalId,
        device_name: 'pos_terminal'
      })

      if (response.data.success) {
        token.value = response.data.data.token
        user.value = response.data.data.user
        branch.value = response.data.data.branch || null

        api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
        localStorage.setItem('auth_token', token.value)

        return { success: true }
      } else {
        throw new Error(response.data.message || 'PIN login failed')
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'PIN login failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch (err) {
      console.error('Logout API error:', err)
    } finally {
      // Clear state regardless of API result
      clearAuth()
      router.push('/login')
    }
  }

  function clearAuth() {
    user.value = null
    token.value = null
    branch.value = null
    permissions.value = []
    error.value = null
    
    delete api.defaults.headers.common['Authorization']
    localStorage.removeItem('auth_token')
  }

  async function fetchUser() {
    if (!token.value) return null

    try {
      const response = await api.get('/auth/user')
      if (response.data.success) {
        user.value = response.data.data.user
        branch.value = response.data.data.branch || branch.value
        permissions.value = response.data.data.permissions || []
        return user.value
      }
    } catch (err) {
      console.error('Fetch user error:', err)
      if (err.response?.status === 401) {
        clearAuth()
      }
    }
    return null
  }

  async function updateProfile(data) {
    isLoading.value = true
    try {
      const response = await api.put('/auth/profile', data)
      if (response.data.success) {
        user.value = { ...user.value, ...response.data.data }
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

  async function changePassword(currentPassword, newPassword) {
    isLoading.value = true
    try {
      const response = await api.post('/auth/change-password', {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: newPassword
      })
      if (response.data.success) {
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

  function setBranch(newBranch) {
    branch.value = newBranch
  }

  function hasPermission(permission) {
    if (isSuperAdmin.value) return true
    return permissions.value.includes(permission)
  }

  function hasRole(roles) {
    if (!userRole.value) return false
    if (typeof roles === 'string') {
      return userRole.value === roles || userRole.value === 'superadmin'
    }
    return roles.includes(userRole.value) || userRole.value === 'superadmin'
  }

  // Initialize from localStorage
  function initAuth() {
    const storedToken = localStorage.getItem('auth_token')
    if (storedToken) {
      token.value = storedToken
      api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
      fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    branch,
    isLoading,
    error,
    permissions,
    
    // Getters
    isAuthenticated,
    userRole,
    isSuperAdmin,
    isAdmin,
    isCashier,
    isWaiter,
    isKitchen,
    branchId,
    userName,
    userInitials,
    
    // Actions
    login,
    loginWithPin,
    logout,
    clearAuth,
    fetchUser,
    updateProfile,
    changePassword,
    setBranch,
    hasPermission,
    hasRole,
    initAuth
  }
}, {
  persist: {
    key: 'auth',
    paths: ['token', 'user', 'branch'],
    storage: localStorage
  }
})
