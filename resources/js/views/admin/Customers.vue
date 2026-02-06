<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Customer Management</h1>
      <button class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <PlusIcon class="w-5 h-5" />
        <span>Add Customer</span>
      </button>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loyalty Points</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                  {{ customer.name.charAt(0) }}
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                  <div class="text-sm text-gray-500">ID: {{ customer.code }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ customer.phone }}</div>
              <div class="text-sm text-gray-500">{{ customer.email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                {{ customer.loyalty_points }} pts
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(customer.last_visit_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="editCustomer(customer)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
              <button @click="viewHistory(customer)" class="text-gray-600 hover:text-gray-900">History</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { formatDate } from '@/utils/formatters'
import { useCustomerStore } from '@/stores/customer'

const customerStore = useCustomerStore()
const customers = ref([])

onMounted(async () => {
  customers.value = await customerStore.fetchCustomers()
})
</script>
