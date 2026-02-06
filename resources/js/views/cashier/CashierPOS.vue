<template>
  <div class="cashier-pos h-screen flex flex-col bg-gray-100">
    <!-- Top Bar -->
    <header class="bg-white shadow-sm border-b px-4 py-2 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h1 class="text-xl font-bold text-gray-800">Cashier POS</h1>
        <div v-if="posSession" class="flex items-center gap-2 text-sm">
          <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">
            Session Active
          </span>
          <span class="text-gray-600">
            Terminal: {{ posSession.terminal?.name }}
          </span>
        </div>
      </div>
      
      <div class="flex items-center gap-4">
        <div class="text-right">
          <p class="text-sm text-gray-600">{{ currentUser?.name }}</p>
          <p class="text-xs text-gray-400">{{ formatDate(new Date()) }}</p>
        </div>
        <button 
          @click="showSessionModal = true"
          class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
        >
          {{ posSession ? 'Session Info' : 'Open Session' }}
        </button>
        <button 
          @click="handleLogout"
          class="p-2 text-gray-500 hover:text-gray-700"
        >
          <i class="fas fa-sign-out-alt w-5 h-5"></i>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden" v-if="posSession">
      <!-- Left Panel: Orders Queue -->
      <div class="w-80 bg-white border-r flex flex-col">
        <div class="p-3 border-b">
          <h2 class="font-semibold text-gray-700">Orders Queue</h2>
          <div class="flex gap-2 mt-2">
            <button 
              v-for="tab in orderTabs" 
              :key="tab.value"
              @click="activeOrderTab = tab.value"
              :class="[
                'px-3 py-1 text-xs rounded-full transition',
                activeOrderTab === tab.value
                  ? 'bg-blue-500 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ tab.label }} ({{ getOrderCount(tab.value) }})
            </button>
          </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 space-y-2">
          <OrderQueueCard 
            v-for="order in filteredOrders" 
            :key="order.id" 
            :order="order" 
            :selected="selectedOrder?.id === order.id"
            @click="selectOrder(order)"
            @pay="initiatePayment(order)"
          />
          
          <div v-if="filteredOrders.length === 0" class="text-center py-8 text-gray-400">
            <i class="fas fa-clipboard-list w-12 h-12 mx-auto mb-2 text-4xl"></i>
            <p>No orders in queue</p>
          </div>
        </div>
      </div>

      <!-- Center Panel: Order Details -->
      <div class="flex-1 flex flex-col bg-gray-50">
        <div v-if="selectedOrder" class="flex-1 flex flex-col">
          <!-- Order Header -->
          <div class="bg-white border-b p-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-bold">Order #{{ selectedOrder.order_number }}</h3>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                  <span>{{ getOrderTypeLabel(selectedOrder.order_type) }}</span>
                  <span v-if="selectedOrder.table">• Table: {{ selectedOrder.table.name }}</span>
                  <span v-if="selectedOrder.waiter">• Waiter: {{ selectedOrder.waiter.name }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span :class="getStatusClass(selectedOrder.status)">
                  {{ getStatusLabel(selectedOrder.status) }}
                </span>
                <button 
                  v-if="canModifyOrder"
                  @click="showEditModal = true"
                  class="p-2 text-blue-500 hover:bg-blue-50 rounded"
                >
                  <i class="fas fa-pencil-alt w-5 h-5"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="flex-1 overflow-y-auto p-4">
            <div class="bg-white rounded-lg shadow">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left p-3 text-sm font-medium text-gray-600">Item</th>
                    <th class="text-center p-3 text-sm font-medium text-gray-600">Qty</th>
                    <th class="text-right p-3 text-sm font-medium text-gray-600">Price</th>
                    <th class="text-right p-3 text-sm font-medium text-gray-600">Subtotal</th>
                    <th class="w-10"></th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="item in selectedOrder.items" :key="item.id" 
                      :class="{ 'opacity-50 line-through': item.is_void }">
                    <td class="p-3">
                      <p class="font-medium">{{ item.item_name }}</p>
                      <div v-if="item.modifiers?.length" class="text-xs text-gray-500">
                        <span v-for="mod in item.modifiers" :key="mod.id" class="mr-2">
                          + {{ mod.modifier_name }}
                        </span>
                      </div>
                      <p v-if="item.notes" class="text-xs text-amber-600">{{ item.notes }}</p>
                    </td>
                    <td class="p-3 text-center">{{ item.quantity }}</td>
                    <td class="p-3 text-right">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="p-3 text-right font-medium">{{ formatCurrency(item.subtotal) }}</td>
                    <td class="p-3">
                      <button 
                        v-if="!item.is_void && canModifyOrder"
                        @click="voidItem(item)"
                        class="p-1 text-red-500 hover:bg-red-50 rounded"
                        title="Void Item"
                      >
                        <i class="fas fa-times w-4 h-4"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Customer Info -->
            <div v-if="selectedOrder.customer" class="mt-4 bg-white rounded-lg shadow p-4">
              <h4 class="font-medium text-gray-700 mb-2">Customer</h4>
              <p class="text-sm">{{ selectedOrder.customer.name }}</p>
              <p class="text-sm text-gray-500">{{ selectedOrder.customer.phone }}</p>
              <p class="text-xs text-blue-500">
                Loyalty Points: {{ selectedOrder.customer.loyalty_points }}
              </p>
            </div>
          </div>

          <!-- Order Summary & Actions -->
          <div class="bg-white border-t p-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal</span>
                  <span>{{ formatCurrency(orderSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Tax ({{ taxRate }}%)</span>
                  <span>{{ formatCurrency(orderTax) }}</span>
                </div>
                <div v-if="orderDiscount > 0" class="flex justify-between text-sm text-green-600">
                  <span>Discount</span>
                  <span>-{{ formatCurrency(orderDiscount) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-2">
                  <span>Total</span>
                  <span>{{ formatCurrency(orderTotal) }}</span>
                </div>
              </div>
              
              <div class="flex flex-col gap-2">
                <button 
                  @click="showDiscountModal = true"
                  class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
                  :disabled="!canModifyOrder"
                >
                  Apply Discount
                </button>
                <button 
                  @click="initiatePayment(selectedOrder)"
                  class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium"
                  :disabled="!canProcessPayment"
                >
                  Process Payment
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- No Order Selected -->
        <div v-else class="flex-1 flex items-center justify-center text-gray-400">
          <div class="text-center">
            <i class="fas fa-hand-pointer w-16 h-16 mx-auto mb-4 text-6xl opacity-50"></i>
            <p class="text-lg">Select an order to process payment</p>
          </div>
        </div>
      </div>

      <!-- Right Panel: Quick Actions & Numpad -->
      <div class="w-80 bg-white border-l flex flex-col">
        <!-- Quick Actions -->
        <div class="p-4 border-b">
          <h3 class="font-semibold text-gray-700 mb-3">Quick Actions</h3>
          <div class="grid grid-cols-2 gap-2">
            <button 
              @click="createNewOrder('takeaway')"
              class="p-3 bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100 text-sm"
            >
              <i class="fas fa-shopping-bag w-6 h-6 mx-auto mb-1 block text-2xl"></i>
              Takeaway
            </button>
            <button 
              @click="showCustomerSearch = true"
              class="p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm"
            >
              <i class="fas fa-user w-6 h-6 mx-auto mb-1 block text-2xl"></i>
              Customer
            </button>
            <button 
              @click="openCashDrawer"
              class="p-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 text-sm"
            >
              <i class="fas fa-cash-register w-6 h-6 mx-auto mb-1 block text-2xl"></i>
              Open Drawer
            </button>
            <button 
              @click="showReportsModal = true"
              class="p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 text-sm"
            >
              <i class="fas fa-chart-bar w-6 h-6 mx-auto mb-1 block text-2xl"></i>
              Reports
            </button>
          </div>
        </div>

        <!-- Session Summary -->
        <div class="p-4 border-b">
          <h3 class="font-semibold text-gray-700 mb-3">Session Summary</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Total Sales</span>
              <span class="font-medium">{{ formatCurrency(posSession?.total_sales || 0) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Cash Sales</span>
              <span>{{ formatCurrency(posSession?.total_cash_sales || 0) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Card Sales</span>
              <span>{{ formatCurrency(posSession?.total_card_sales || 0) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Orders</span>
              <span>{{ posSession?.total_orders || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tips</span>
              <span>{{ formatCurrency(posSession?.total_tips || 0) }}</span>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="flex-1 overflow-y-auto p-4">
          <h3 class="font-semibold text-gray-700 mb-3">Recent Transactions</h3>
          <div class="space-y-2">
            <div 
              v-for="tx in recentTransactions" 
              :key="tx.id"
              class="p-2 bg-gray-50 rounded text-sm"
            >
              <div class="flex justify-between">
                <span class="font-medium">{{ tx.invoice_number }}</span>
                <span :class="tx.status === 'paid' ? 'text-green-600' : 'text-amber-600'">
                  {{ formatCurrency(tx.total_amount) }}
                </span>
              </div>
              <p class="text-xs text-gray-500">{{ formatTime(tx.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No Session Warning -->
    <div v-else class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle w-16 h-16 text-amber-500 mx-auto mb-4 text-6xl"></i>
        <h2 class="text-xl font-bold text-gray-700 mb-2">No Active Session</h2>
        <p class="text-gray-500 mb-4">You need to open a POS session to start processing orders.</p>
        <button 
          @click="showSessionModal = true"
          class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
        >
          Open Session
        </button>
      </div>
    </div>

    <!-- Modals -->
    <PaymentModal 
      v-if="showPaymentModal"
      v-model="showPaymentModal"
      :order-items="selectedOrder?.items || []"
      :total-amount="orderTotal"
      @process="handlePaymentComplete"
    />

    <SessionModal 
      v-if="showSessionModal"
      :session="posSession"
      @close="showSessionModal = false"
      @open="handleOpenSession"
      @close-session="handleCloseSession"
    />

    <DiscountModal 
      v-if="showDiscountModal"
      v-model="showDiscountModal"
      :current-discount="selectedOrder?.discount"
      @apply="applyDiscount"
      @clear="applyDiscount(null)"
    />

    <CustomerSearchModal 
      v-if="showCustomerSearch"
      @close="showCustomerSearch = false"
      @select="handleCustomerSelect"
    />

    <ReportsModal 
      v-if="showReportsModal"
      :session="posSession"
      @close="showReportsModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOrdersStore } from '@/stores/orders'
import { usePosStore } from '@/stores/pos'
// Use alias or relative path depending on component location
import PaymentModal from '@/components/pos/PaymentModal.vue'
import DiscountModal from '@/components/pos/DiscountModal.vue'
import SessionModal from '@/components/cashier/SessionModal.vue'
import OrderQueueCard from '@/components/cashier/OrderQueueCard.vue'
import CustomerSearchModal from '@/components/cashier/CustomerSearchModal.vue'
import ReportsModal from '@/components/cashier/ReportsModal.vue'
import api from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()
const ordersStore = useOrdersStore()
const posStore = usePosStore()

// State
const activeOrderTab = ref('completed')
const showPaymentModal = ref(false)
const showSessionModal = ref(false)
const showDiscountModal = ref(false)
const showCustomerSearch = ref(false)
const showReportsModal = ref(false)
const showEditModal = ref(false)
const selectedOrder = ref(null)
const recentTransactions = ref([])

// Constants
const orderTabs = [
  { label: 'Completed', value: 'completed' },
  { label: 'Active', value: 'active' },
  { label: 'Paid', value: 'paid' }
]

// Computed
const currentUser = computed(() => authStore.user)
const posSession = computed(() => posStore.currentSession)
const taxRate = computed(() => authStore.branch?.gst_rate || 16)
const canModifyOrder = computed(() => selectedOrder.value && ['draft', 'open', 'hold'].includes(selectedOrder.value.status))
const canProcessPayment = computed(() => selectedOrder.value && selectedOrder.value.status !== 'paid' && selectedOrder.value.items.length > 0)

const filteredOrders = computed(() => {
  if (activeOrderTab.value === 'completed') {
    return ordersStore.completedOrders.filter(o => o.status === 'completed')
  } else if (activeOrderTab.value === 'active') {
    return ordersStore.completedOrders.filter(o => ['draft', 'open', 'hold', 'sent_to_kitchen', 'preparing', 'ready'].includes(o.status))
  } else if (activeOrderTab.value === 'paid') {
    return ordersStore.completedOrders.filter(o => o.status === 'paid')
  }
  return []
})

const orderSubtotal = computed(() => {
  if (!selectedOrder.value?.items) return 0
  return selectedOrder.value.items.reduce((sum, item) => sum + (item.subtotal || 0), 0)
})

const orderDiscount = computed(() => {
  return selectedOrder.value?.discount_amount || 0
})

const orderTax = computed(() => {
  const afterDiscount = orderSubtotal.value - orderDiscount.value
  return Math.round(afterDiscount * (taxRate.value / 100))
})

const orderTotal = computed(() => {
  return orderSubtotal.value - orderDiscount.value + orderTax.value
})

// Methods
function getOrderCount(tabValue) {
  if (tabValue === 'completed') return ordersStore.completedOrders.filter(o => o.status === 'completed').length
  if (tabValue === 'active') return ordersStore.completedOrders.filter(o => ['draft', 'open', 'hold', 'sent_to_kitchen', 'preparing', 'ready'].includes(o.status)).length
  if (tabValue === 'paid') return ordersStore.completedOrders.filter(o => o.status === 'paid').length
  return 0
}

function selectOrder(order) {
  console.log('Order selected:', order)
  selectedOrder.value = order
}

function initiatePayment(order) {
  selectedOrder.value = order
  showPaymentModal.value = true
}

function getOrderTypeLabel(type) {
  const types = { dine_in: 'Dine In', takeaway: 'Takeaway', delivery: 'Delivery' }
  return types[type] || type
}

function getStatusClass(status) {
  const classes = {
    paid: 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs',
    completed: 'bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs',
    cancelled: 'bg-red-100 text-red-800 px-2 py-1 rounded text-xs',
    draft: 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs'
}

function getStatusLabel(status) {
  return status?.replace(/_/g, ' ').toUpperCase() || 'UNKNOWN'
}

function formatCurrency(value) {
  return `Rs. ${(value || 0).toLocaleString()}`
}

function formatDate(date) {
  return date.toLocaleDateString()
}

function formatTime(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

async function handlePaymentComplete(paymentData) {
  try {
    await ordersStore.processPayment(selectedOrder.value.id, paymentData)
    showPaymentModal.value = false
    selectedOrder.value = null
    await loadRecentTransactions()
    await posStore.refreshSession()
    window.$toast?.success('Payment successful')
  } catch (error) {
    console.error('Payment failed:', error)
    window.$toast?.error('Payment failed')
  }
}

async function handleOpenSession(data) {
  try {
    await posStore.openSession(data)
    showSessionModal.value = false
    await loadOrders()
    window.$toast?.success('Session opened')
  } catch (error) {
    console.error('Failed to open session:', error)
    window.$toast?.error('Failed to open session')
  }
}

async function handleCloseSession(data) {
  try {
    await posStore.closeSession(data)
    showSessionModal.value = false
    window.$toast?.success('Session closed')
  } catch (error) {
    console.error('Failed to close session:', error)
    window.$toast?.error('Failed to close session')
  }
}

async function voidItem(item) {
  if (!confirm('Are you sure you want to void this item?')) return
  
  const reason = prompt('Enter void reason:')
  if (!reason) return

  try {
    await ordersStore.voidOrderItem(selectedOrder.value.id, item.id, reason)
    await loadOrders()
    window.$toast?.success('Item voided')
  } catch (error) {
    console.error('Failed to void item:', error)
    window.$toast?.error('Failed to void item')
  }
}

async function applyDiscount(discountData) {
  try {
    await ordersStore.applyDiscount(selectedOrder.value.id, discountData)
    showDiscountModal.value = false
    await loadOrders()
    window.$toast?.success('Discount applied')
  } catch (error) {
    console.error('Failed to apply discount:', error)
    window.$toast?.error('Failed to apply discount')
  }
}

function handleCustomerSelect(customer) {
  if (selectedOrder.value) {
    ordersStore.assignCustomer(selectedOrder.value.id, customer.id)
  }
  showCustomerSearch.value = false
  window.$toast?.success(`Customer ${customer.name} selected`)
}

async function createNewOrder(type) {
  try {
    const result = await ordersStore.createOrder({ order_type: type })
    if (result.success) {
       selectedOrder.value = result.order
       window.$toast?.success('New order created')
    }
  } catch (error) {
    console.error('Failed to create order:', error)
    window.$toast?.error('Failed to create order')
  }
}

function openCashDrawer() {
  // Send command to cash drawer
  window.electronAPI?.openCashDrawer()
  window.$toast?.info('Opening cash drawer...')
}

async function loadOrders() {
  await ordersStore.fetchOrders({
    branch_id: authStore.branch?.id,
    status: ['ready', 'served', 'completed']
  })
}

async function loadRecentTransactions() {
  // Load recent invoices for the session
  recentTransactions.value = await ordersStore.getRecentInvoices(5)
}

function handleLogout() {
  if (posSession.value) {
    if(!confirm('You have an active session. Are you sure you want to logout?')) return;
  }
  authStore.logout()
  router.push('/login')
}

// Lifecycle
onMounted(async () => {
  await posStore.checkActiveSession()
  if (posSession.value) {
    await loadOrders()
    await loadRecentTransactions()
  }
})

// Auto-refresh orders
let refreshInterval = null
onMounted(() => {
  refreshInterval = setInterval(() => {
    if (posSession.value) {
      loadOrders()
    }
  }, 30000) // Refresh every 30 seconds
})

import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>