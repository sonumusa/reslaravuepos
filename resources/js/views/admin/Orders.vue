<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Orders Management</h1>
      <div class="flex gap-2">
        <div class="relative">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search orders..." 
            class="pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
          <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
        </div>
        <button 
            @click="openFilterModal"
            class="flex items-center gap-2 px-4 py-2 bg-white border rounded-lg hover:bg-gray-50"
        >
          <FunnelIcon class="w-5 h-5 text-gray-500" />
          <span>Filter</span>
        </button>
        <button 
            @click="createNewOrder"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          <PlusIcon class="w-5 h-5" />
          <span>New Order</span>
        </button>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">#{{ order.order_number }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ order.customer?.name || 'Walk-in Customer' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ order.items_count }} items</td>
            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ formatCurrency(order.total) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(order.status)">{{ order.status }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ formatDate(order.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="viewOrder(order)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
              <button @click="deleteOrder(order)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { MagnifyingGlassIcon, FunnelIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { formatCurrency, formatDate } from '@/utils/formatters'
import { useOrderStore } from '@/stores/order'
import { useRouter } from 'vue-router'

const router = useRouter()
const orderStore = useOrderStore()
const orders = ref([])
const searchQuery = ref('')

function getStatusClass(status) {
  const classes = {
    paid: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800',
    pending: 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800',
    cancelled: 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800',
    draft: 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800'
  }
  return classes[status] || classes.draft
}

function createNewOrder() {
    router.push('/pos/tables'); // Or /waiter depending on flow
}

function openFilterModal() {
    console.log('Filter modal clicked');
    // Implement filter modal logic
}

function viewOrder(order) {
    console.log('View order:', order);
    // router.push(`/admin/orders/${order.id}`);
}

function deleteOrder(order) {
    if(confirm('Are you sure you want to delete this order?')) {
        console.log('Delete order:', order);
        // await orderStore.deleteOrder(order.id);
        // orders.value = await orderStore.fetchOrders();
    }
}

onMounted(async () => {
  orders.value = await orderStore.fetchOrders()
})
</script>
