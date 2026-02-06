<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
      <div class="flex gap-2">
        <input 
          v-model="startDate" 
          type="date" 
          class="border rounded-lg px-3 py-2 bg-white text-gray-900"
        >
        <input 
          v-model="endDate" 
          type="date" 
          class="border rounded-lg px-3 py-2 bg-white text-gray-900"
        >
        <button 
          @click="loadReports"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Load Reports
        </button>
      </div>
    </div>

    <!-- Daily Sales -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-900">Daily Sales Summary</h3>
      <div v-if="dailySales" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <p class="text-gray-500 text-sm">Total Sales</p>
          <p class="text-2xl font-bold text-green-600">Rs. {{ (dailySales.total_sales || 0).toLocaleString() }}</p>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Total Orders</p>
          <p class="text-2xl font-bold text-blue-600">{{ dailySales.total_orders || 0 }}</p>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Average Order</p>
          <p class="text-2xl font-bold text-purple-600">
            Rs. {{ dailySales.total_orders > 0 ? Math.round(dailySales.total_sales / dailySales.total_orders).toLocaleString() : 0 }}
          </p>
        </div>
      </div>
      <div v-else class="text-center text-gray-500 py-8">No data available</div>
    </div>

    <!-- Top Selling Items -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-900">Top Selling Items</h3>
      <div v-if="topItems && topItems.length > 0" class="space-y-3">
        <div v-for="(item, index) in topItems" :key="index" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-3">
            <span class="text-2xl font-bold text-gray-400">#{{ index + 1 }}</span>
            <div>
              <p class="font-medium text-gray-900">{{ item.name }}</p>
              <p class="text-sm text-gray-500">{{ item.total_quantity }} sold</p>
            </div>
          </div>
          <p class="font-bold text-green-600">Rs. {{ parseFloat(item.total_sales || 0).toLocaleString() }}</p>
        </div>
      </div>
      <div v-else class="text-center text-gray-500 py-8">No items sold in selected period</div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-900">Payment Methods</h3>
      <div v-if="paymentMethods && paymentMethods.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="method in paymentMethods" :key="method.payment_method" class="p-4 border rounded-lg">
          <p class="text-gray-500 text-sm capitalize">{{ method.payment_method }}</p>
          <p class="text-xl font-bold text-gray-900">Rs. {{ parseFloat(method.total || 0).toLocaleString() }}</p>
          <p class="text-sm text-gray-400">{{ method.count }} transactions</p>
        </div>
      </div>
      <div v-else class="text-center text-gray-500 py-8">No payment data</div>
    </div>

    <!-- Staff Performance -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-900">Staff Performance</h3>
      <div v-if="staffPerformance && staffPerformance.length > 0" class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Name</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Orders</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Total Sales</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="staff in staffPerformance" :key="staff.name">
              <td class="px-4 py-3 text-gray-900">{{ staff.name }}</td>
              <td class="px-4 py-3 text-gray-600">{{ staff.orders_count }}</td>
              <td class="px-4 py-3 font-medium text-green-600">Rs. {{ parseFloat(staff.total_sales || 0).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center text-gray-500 py-8">No staff data</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useReportStore } from '@/stores/report'

const reportStore = useReportStore()
const startDate = ref(new Date().toISOString().split('T')[0])
const endDate = ref(new Date().toISOString().split('T')[0])

const dailySales = ref(null)
const topItems = ref([])
const paymentMethods = ref([])
const staffPerformance = ref([])

async function loadReports() {
  dailySales.value = await reportStore.getDailySales(startDate.value)
  topItems.value = await reportStore.getItemsSold(startDate.value, endDate.value)
  paymentMethods.value = await reportStore.getPaymentMethods(startDate.value, endDate.value)
  staffPerformance.value = await reportStore.getStaffPerformance(startDate.value, endDate.value)
}

onMounted(() => {
  loadReports()
})
</script>