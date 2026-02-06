<template>
  <div 
    @click="$emit('click')" 
    :class="[
      'p-3 rounded-lg border-2 cursor-pointer transition',
      selected 
        ? 'border-blue-500 bg-blue-50' 
        : 'border-gray-200 bg-white hover:border-gray-300'
    ]"
  >
    <div class="flex justify-between items-start mb-2">
      <div>
        <span class="font-bold text-gray-800">#{{ order.order_number }}</span>
        <span :class="getTypeClass(order.order_type)" class="ml-2 text-xs px-2 py-0.5 rounded">
          {{ getTypeLabel(order.order_type) }}
        </span>
      </div>
      <span :class="getStatusClass(order.status)" class="text-xs px-2 py-1 rounded-full">
        {{ getStatusLabel(order.status) }}
      </span>
    </div>
    
    <div class="text-sm text-gray-600 mb-2">
      <span v-if="order.table">Table: {{ order.table.name }}</span>
      <span v-if="order.customer" class="ml-2">{{ order.customer.name }}</span>
    </div>
    
    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-500">
        {{ order.items?.length || 0 }} items
      </div>
      <div class="font-bold text-lg">
        {{ formatCurrency(orderTotal) }}
      </div>
    </div>
    
    <div class="mt-2 flex justify-between items-center">
      <span class="text-xs text-gray-400">{{ formatTime(order.created_at) }}</span>
      <button 
        v-if="canPay" 
        @click.stop="$emit('pay')" 
        class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600"
      >
        Pay
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  order: { type: Object, required: true },
  selected: { type: Boolean, default: false }
})

defineEmits(['click', 'pay'])

const orderTotal = computed(() => {
  return props.order.items?.reduce((sum, item) => {
    if (!item.is_void) {
      return sum + parseFloat(item.subtotal || 0)
    }
    return sum
  }, 0) || 0
})

const canPay = computed(() => {
  return ['ready', 'served', 'completed', 'sent_to_kitchen', 'preparing'].includes(props.order.status)
})

function formatCurrency(value) {
  return `Rs. ${(value || 0).toLocaleString()}`
}

function formatTime(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function getTypeLabel(type) {
  const labels = { dine_in: 'Dine In', takeaway: 'Takeaway', delivery: 'Delivery' }
  return labels[type] || type
}

function getTypeClass(type) {
  const classes = {
    dine_in: 'bg-blue-100 text-blue-700',
    takeaway: 'bg-amber-100 text-amber-700',
    delivery: 'bg-purple-100 text-purple-700'
  }
  return classes[type] || 'bg-gray-100 text-gray-700'
}

function getStatusLabel(status) {
  const labels = { 
    ready: 'Ready', 
    served: 'Served', 
    completed: 'Completed',
    sent_to_kitchen: 'Kitchen',
    preparing: 'Preparing',
    draft: 'Draft'
  }
  return labels[status] || status
}

function getStatusClass(status) {
  const classes = {
    ready: 'bg-green-100 text-green-700',
    served: 'bg-blue-100 text-blue-700',
    completed: 'bg-purple-100 text-purple-700',
    sent_to_kitchen: 'bg-orange-100 text-orange-700',
    preparing: 'bg-orange-100 text-orange-700',
    draft: 'bg-gray-100 text-gray-700'
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}
</script>