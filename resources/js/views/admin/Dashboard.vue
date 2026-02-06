<template>
  <div>
    <!-- Dashboard specific header actions using portal or similar if needed, 
         but for now we'll keep the controls inside the page and maybe move them up later.
         Or we can put them in the page content. -->
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div><!-- Spacer or Title override --></div>
        <div class="flex items-center gap-4">
          <!-- Branch Selector -->
          <select 
            v-if="isSuperAdmin" 
            v-model="selectedBranch" 
            class="px-3 py-2 border rounded-lg text-sm bg-white"
          >
            <option :value="null">All Branches</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>

          <!-- Date Range -->
          <div class="flex items-center gap-2">
            <input 
              type="date" 
              v-model="dateRange.start" 
              class="px-3 py-2 border rounded-lg text-sm" 
            />
            <span class="text-gray-400">to</span>
            <input 
              type="date" 
              v-model="dateRange.end" 
              class="px-3 py-2 border rounded-lg text-sm" 
            />
          </div>

          <!-- Refresh -->
          <button 
            @click="refreshDashboard" 
            :disabled="isLoading" 
            class="p-2 hover:bg-gray-100 rounded-lg bg-white border"
          >
            <ArrowPathIcon :class="['w-5 h-5', isLoading ? 'animate-spin' : '']" />
          </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <StatCard 
        title="Today's Sales" 
        :value="formatCurrency(stats.todaySales)" 
        :change="stats.salesChange" 
        icon="CurrencyIcon" 
        color="green" 
      />
      <StatCard 
        title="Orders" 
        :value="stats.todayOrders" 
        :change="stats.ordersChange" 
        icon="ClipboardIcon" 
        color="blue" 
      />
      <StatCard 
        title="Average Order" 
        :value="formatCurrency(stats.averageOrder)" 
        :change="stats.avgChange" 
        icon="CalculatorIcon" 
        color="purple" 
      />
      <StatCard 
        title="PRA Pending" 
        :value="stats.praPending" 
        icon="DocumentIcon" 
        color="amber" 
        :alert="stats.praPending > 0" 
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Sales Overview</h3>
        <SalesChart :data="salesChartData" />
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Order Types</h3>
        <OrderTypeChart :data="orderTypeData" />
      </div>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Top Selling Items -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Top Selling Items</h3>
        <div class="space-y-3">
          <div 
            v-for="(item, index) in topItems" 
            :key="item.id" 
            class="flex items-center gap-3"
          >
            <span :class="[ 
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold', 
              index === 0 ? 'bg-amber-100 text-amber-600' : 
              index === 1 ? 'bg-gray-100 text-gray-600' : 
              index === 2 ? 'bg-orange-100 text-orange-600' : 
              'bg-gray-50 text-gray-500' 
            ]">
              {{ index + 1 }}
            </span>
            <div class="flex-1 min-w-0">
              <p class="font-medium truncate">{{ item.name }}</p>
              <p class="text-sm text-gray-500">{{ item.quantity }} sold</p>
            </div>
            <span class="text-sm font-medium">{{ formatCurrency(item.revenue) }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Recent Orders</h3>
        <div class="space-y-3">
          <div 
            v-for="order in recentOrders" 
            :key="order.id" 
            class="flex items-center justify-between py-2 border-b last:border-0"
          >
            <div>
              <p class="font-medium">#{{ order.order_number }}</p>
              <p class="text-sm text-gray-500">{{ order.items_count }} items</p>
            </div>
            <div class="text-right">
              <p class="font-medium">{{ formatCurrency(order.total) }}</p>
              <span :class="getStatusClass(order.status)">{{ order.status }}</span>
            </div>
          </div>
        </div>
        <router-link to="/admin/orders" class="block mt-4 text-blue-500 text-sm text-center">
          View All Orders →
        </router-link>
      </div>

      <!-- Active Sessions -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Active POS Sessions</h3>
        <div class="space-y-3">
          <div 
            v-for="session in activeSessions" 
            :key="session.id" 
            class="flex items-center justify-between py-2 border-b last:border-0"
          >
            <div>
              <p class="font-medium">{{ session.user.name }}</p>
              <p class="text-sm text-gray-500">{{ session.terminal.name }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium">{{ formatCurrency(session.total_sales) }}</p>
              <p class="text-xs text-gray-500">{{ session.total_orders }} orders</p>
            </div>
          </div>
          <div v-if="activeSessions.length === 0" class="text-center text-gray-400 py-4">
            No active sessions
          </div>
        </div>
      </div>
    </div>

    <!-- PRA Status Section -->
    <div v-if="stats.praPending > 0" class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <ExclamationTriangleIcon class="w-8 h-8 text-amber-500" />
          <div>
            <h3 class="text-lg font-semibold text-amber-800">PRA Invoices Pending</h3>
            <p class="text-sm text-amber-600">
              {{ stats.praPending }} invoices need to be submitted to PRA
            </p>
          </div>
        </div>
        <button 
          @click="retryPraSubmissions" 
          class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600"
        >
          Retry Submissions
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import { formatCurrency } from '@/utils/formatters'
import StatCard from '@/components/admin/StatCard.vue'
import SalesChart from '@/components/admin/SalesChart.vue'
import OrderTypeChart from '@/components/admin/OrderTypeChart.vue'
import {
  ArrowPathIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const dashboardStore = useDashboardStore()

const isLoading = ref(false)
const selectedBranch = ref(null)
const dateRange = ref({
  start: new Date().toISOString().split('T')[0],
  end: new Date().toISOString().split('T')[0]
})

const isSuperAdmin = computed(() => authStore.user?.role === 'superadmin')
const branches = computed(() => dashboardStore.branches)
const stats = computed(() => dashboardStore.stats)
const topItems = computed(() => dashboardStore.topItems)
const recentOrders = computed(() => dashboardStore.recentOrders)
const activeSessions = computed(() => dashboardStore.activeSessions)
const salesChartData = computed(() => dashboardStore.salesChartData)
const orderTypeData = computed(() => dashboardStore.orderTypeData)

function getStatusClass(status) {
  const classes = {
    paid: 'text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded',
    completed: 'text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded',
    pending: 'text-xs px-2 py-0.5 bg-amber-100 text-amber-700 rounded'
  }
  return classes[status] || 'text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded'
}

async function refreshDashboard() {
  isLoading.value = true
  try {
    await dashboardStore.fetchDashboardData({
      branch_id: selectedBranch.value,
      start_date: dateRange.value.start,
      end_date: dateRange.value.end
    })
  } finally {
    isLoading.value = false
  }
}

async function retryPraSubmissions() {
  try {
    await dashboardStore.retryPraSubmissions()
    await refreshDashboard()
  } catch (error) {
    console.error('Failed to retry PRA submissions:', error)
  }
}

watch([selectedBranch, dateRange], () => {
  refreshDashboard()
}, { deep: true })

onMounted(() => {
  refreshDashboard()
})
</script>
