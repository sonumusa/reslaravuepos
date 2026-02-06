<template>
  <div class="kds h-screen flex flex-col bg-gray-900 text-white">
    <!-- Top Bar -->
    <header class="bg-gray-800 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-green-400">Kitchen Display</h1>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500'"></span>
          <span class="text-sm text-gray-400">{{ isOnline ? 'Online' : 'Offline' }}</span>
        </div>
      </div>
      
      <div class="flex items-center gap-6">
        <!-- Stats -->
        <div class="flex items-center gap-6 text-sm">
          <div class="text-center">
            <p class="text-2xl font-bold text-amber-400">{{ pendingCount }}</p>
            <p class="text-gray-400">Pending</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-400">{{ preparingCount }}</p>
            <p class="text-gray-400">Preparing</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-green-400">{{ readyCount }}</p>
            <p class="text-gray-400">Ready</p>
          </div>
        </div>

        <!-- Clock -->
        <div class="text-right">
          <p class="text-2xl font-mono">{{ currentTime }}</p>
          <p class="text-sm text-gray-400">{{ currentDate }}</p>
        </div>

        <!-- Settings -->
        <button 
          @click="showSettings = true" 
          class="p-2 hover:bg-gray-700 rounded-lg"
        >
          <i class="fas fa-cog w-6 h-6"></i>
        </button>
      </div>
    </header>

    <!-- Orders Grid -->
    <div class="flex-1 p-4 overflow-hidden">
      <div class="grid grid-cols-4 gap-4 h-full auto-rows-max overflow-y-auto">
        <KitchenOrderCard 
          v-for="order in sortedOrders" 
          :key="order.id" 
          :order="order" 
          :elapsed-time="getElapsedTime(order)" 
          @acknowledge="acknowledgeOrder(order)" 
          @start="startPreparing(order)" 
          @ready="markReady(order)" 
          @item-ready="markItemReady" 
          @recall="recallOrder(order)" 
        />
        
        <!-- Empty State -->
        <div 
          v-if="sortedOrders.length === 0" 
          class="col-span-4 flex items-center justify-center h-64" 
        >
          <div class="text-center text-gray-500">
            <i class="fas fa-utensils w-24 h-24 mx-auto mb-4 opacity-50 text-6xl block"></i>
            <p class="text-xl">No orders in queue</p>
            <p class="text-sm">New orders will appear here automatically</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Actions -->
    <footer class="bg-gray-800 px-6 py-3 flex items-center justify-between">
      <div class="flex gap-4">
        <button 
          @click="filterStatus = 'all'" 
          :class="[
            'px-4 py-2 rounded-lg transition',
            filterStatus === 'all' ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600'
          ]"
        >
          All Orders
        </button>
        <button 
          @click="filterStatus = 'pending'" 
          :class="[
            'px-4 py-2 rounded-lg transition',
            filterStatus === 'pending' ? 'bg-amber-600' : 'bg-gray-700 hover:bg-gray-600'
          ]"
        >
          Pending
        </button>
        <button 
          @click="filterStatus = 'preparing'" 
          :class="[
            'px-4 py-2 rounded-lg transition',
            filterStatus === 'preparing' ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600'
          ]"
        >
          Preparing
        </button>
        <button 
          @click="filterStatus = 'ready'" 
          :class="[
            'px-4 py-2 rounded-lg transition',
            filterStatus === 'ready' ? 'bg-green-600' : 'bg-gray-700 hover:bg-gray-600'
          ]"
        >
          Ready
        </button>
      </div>
      
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            v-model="soundEnabled" 
            class="w-4 h-4 rounded" 
          />
          <span class="text-sm">Sound Alerts</span>
        </label>
        
        <button 
          @click="refreshOrders" 
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center gap-2"
        >
          <i class="fas fa-sync-alt w-5 h-5" :class="{ 'fa-spin': isLoading }"></i>
          Refresh
        </button>
      </div>
    </footer>

    <!-- New Order Alert -->
    <NewOrderAlert 
      v-if="showNewOrderAlert" 
      :order="newOrder" 
      @dismiss="dismissAlert" 
    />

    <!-- Settings Modal -->
    <KDSSettingsModal 
      v-if="showSettings" 
      @close="showSettings = false" 
      @save="saveSettings" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useOrdersStore } from '@/stores/orders'
import { useAuthStore } from '@/stores/auth'
import { useSync } from '@/composables/useSync'
import KitchenOrderCard from '@/components/kitchen/KitchenOrderCard.vue'
import NewOrderAlert from '@/components/kitchen/NewOrderAlert.vue'
import KDSSettingsModal from '@/components/kitchen/KDSSettingsModal.vue'

const ordersStore = useOrdersStore()
const authStore = useAuthStore()
const { isOnline } = useSync()

const isLoading = ref(false)
const filterStatus = ref('all')
const showSettings = ref(false)
const soundEnabled = ref(true)
const showNewOrderAlert = ref(false)
const newOrder = ref(null)

const currentTime = ref('')
const currentDate = ref('')

// Get kitchen orders
const kitchenOrders = computed(() => {
  return ordersStore.orders.filter(order => 
    ['sent_to_kitchen', 'preparing', 'ready'].includes(order.status)
  )
})

const sortedOrders = computed(() => {
  let orders = [...kitchenOrders.value]
  
  if (filterStatus.value !== 'all') {
    const statusMap = {
      'pending': 'sent_to_kitchen',
      'preparing': 'preparing',
      'ready': 'ready'
    }
    orders = orders.filter(o => o.status === statusMap[filterStatus.value])
  }
  
  // Sort by priority first, then by time
  return orders.sort((a, b) => {
    if (a.is_priority && !b.is_priority) return -1
    if (!a.is_priority && b.is_priority) return 1
    return new Date(a.sent_to_kitchen_at || a.created_at) - new Date(b.sent_to_kitchen_at || b.created_at)
  })
})

const pendingCount = computed(() => 
  kitchenOrders.value.filter(o => o.status === 'sent_to_kitchen').length
)
const preparingCount = computed(() => 
  kitchenOrders.value.filter(o => o.status === 'preparing').length
)
const readyCount = computed(() => 
  kitchenOrders.value.filter(o => o.status === 'ready').length
)

function getElapsedTime(order) {
  const start = new Date(order.sent_to_kitchen_at || order.created_at)
  const now = new Date()
  const diffMs = now - start
  const minutes = Math.floor(diffMs / 60000)
  const seconds = Math.floor((diffMs % 60000) / 1000)
  return { minutes, seconds, total: diffMs }
}

async function acknowledgeOrder(order) {
  try {
    await ordersStore.updateOrder(order.id, { status: 'preparing' })
    playSound('acknowledge')
  } catch (error) {
    console.error('Failed to acknowledge order:', error)
  }
}

async function startPreparing(order) {
  try {
    await ordersStore.updateOrder(order.id, { status: 'preparing' })
  } catch (error) {
    console.error('Failed to start preparing:', error)
  }
}

async function markReady(order) {
  try {
    await ordersStore.updateOrder(order.id, { status: 'ready' })
    playSound('ready')
  } catch (error) {
    console.error('Failed to mark ready:', error)
  }
}

async function markItemReady(orderId, itemId) {
  try {
    // Assuming we have a method for item update, or we update the whole order items locally then save
    // For now, let's assume we can update item status via API or store
    // ordersStore.updateOrderItemStatus(orderId, itemId, 'ready') 
    // Since updateOrderItemStatus doesn't exist in orders.js, we might need to implement it or use updateOrder
    console.warn('Item level update not fully implemented in store yet')
  } catch (error) {
    console.error('Failed to mark item ready:', error)
  }
}

async function recallOrder(order) {
  try {
    await ordersStore.updateOrder(order.id, { status: 'preparing' })
  } catch (error) {
    console.error('Failed to recall order:', error)
  }
}

async function refreshOrders() {
  isLoading.value = true
  try {
    await ordersStore.fetchOrders({
      branch_id: authStore.branch?.id,
      status: ['sent_to_kitchen', 'preparing', 'ready']
    })
  } finally {
    isLoading.value = false
  }
}

function playSound(type) {
  if (!soundEnabled.value) return
  
  try {
    const audio = new Audio(`/sounds/${type}.mp3`)
    audio.play().catch(e => console.log('Audio play failed', e))
  } catch (e) {
    console.log('Sound not available')
  }
}

function dismissAlert() {
  showNewOrderAlert.value = false
  newOrder.value = null
}

function saveSettings(settings) {
  soundEnabled.value = settings.soundEnabled
  localStorage.setItem('kds_settings', JSON.stringify(settings))
  showSettings.value = false
}

function updateClock() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'short',
    day: 'numeric'
  })
}

// Real-time updates via WebSocket or polling
let pollingInterval = null
let clockInterval = null

onMounted(async () => {
  // Load saved settings
  const savedSettings = localStorage.getItem('kds_settings')
  if (savedSettings) {
    const settings = JSON.parse(savedSettings)
    soundEnabled.value = settings.soundEnabled ?? true
  }
  
  // Initial load
  await refreshOrders()
  
  // Start clock
  updateClock()
  clockInterval = setInterval(updateClock, 1000)
  
  // Start polling for new orders (every 5 seconds)
  pollingInterval = setInterval(refreshOrders, 5000)
  
  // Listen for WebSocket events (if available)
  window.Echo?.private(`branch.${authStore.branch?.id}`)
    .listen('NewOrderSentToKitchen', (event) => {
      newOrder.value = event.order
      showNewOrderAlert.value = true
      playSound('newOrder')
      refreshOrders()
    })
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
  if (clockInterval) clearInterval(clockInterval)
})

// Watch for new orders
watch(pendingCount, (newCount, oldCount) => {
  if (newCount > oldCount) {
    playSound('newOrder')
  }
})
</script>

<style scoped>
.kds {
  user-select: none;
}

/* Prevent screen burn-in with subtle animation */
.kds::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  animation: subtle-shift 120s infinite linear;
  background: linear-gradient(45deg, transparent 98%, rgba(255,255,255,0.02) 100%);
}

@keyframes subtle-shift {
  0%, 100% { transform: translate(0, 0); }
  25% { transform: translate(1px, 0); }
  50% { transform: translate(0, 1px); }
  75% { transform: translate(-1px, 0); }
}
</style>