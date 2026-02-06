import { defineStore } from 'pinia'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: {
      todaySales: 0,
      salesChange: 0,
      todayOrders: 0,
      ordersChange: 0,
      averageOrder: 0,
      avgChange: 0,
      praPending: 0
    },
    topItems: [],
    recentOrders: [],
    activeSessions: [],
    branches: [],
    salesChartData: {
      labels: [],
      values: []
    },
    orderTypeData: {
      labels: ['Dine In', 'Takeaway', 'Delivery'],
      values: [0, 0, 0]
    }
  }),

  actions: {
    async fetchDashboardData(params = {}) {
      try {
        const response = await api.get('/admin/dashboard', { params })
        const data = response.data.data

        this.stats = data.stats
        this.topItems = data.topItems
        this.recentOrders = data.recentOrders
        this.activeSessions = data.activeSessions
        this.salesChartData = data.salesChart
        this.orderTypeData = data.orderTypes

      } catch (error) {
        console.error('Failed to fetch dashboard data:', error)
        throw error
      }
    },

    async fetchBranches() {
      try {
        const response = await api.get('/branches')
        this.branches = response.data.data
      } catch (error) {
        console.error('Failed to fetch branches:', error)
      }
    },

    async retryPraSubmissions() {
      try {
        // await api.post('/admin/pra/retry-pending')
        await new Promise(resolve => setTimeout(resolve, 1000))
        this.stats.praPending = 0
      } catch (error) {
        console.error('Failed to retry PRA submissions:', error)
        throw error
      }
    }
  }
})
