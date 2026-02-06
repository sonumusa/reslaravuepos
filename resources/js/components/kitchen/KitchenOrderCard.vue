<template>
  <div 
    :class="[
      'rounded-xl overflow-hidden transition-all duration-300 bg-gray-800 border border-gray-700',
      cardClass,
      order.is_priority ? 'ring-4 ring-red-500 animate-pulse-subtle' : ''
    ]"
  >
    <!-- Header -->
    <div :class="['px-4 py-3 flex justify-between items-center', headerClass]">
      <div class="flex items-center gap-3">
        <span class="text-2xl font-bold">#{{ order.order_number }}</span>
        <span 
          v-if="order.is_priority" 
          class="px-2 py-1 bg-red-500 text-white text-xs rounded-full font-bold animate-pulse"
        >
          PRIORITY
        </span>
      </div>
      <div class="text-right">
        <div :class="['text-2xl font-mono font-bold', timerClass]">
          {{ formatElapsedTime }}
        </div>
        <div class="text-xs opacity-75">{{ formatOrderTime }}</div>
      </div>
    </div>

    <!-- Order Info -->
    <div class="px-4 py-2 bg-black/20 flex justify-between text-sm">
      <div class="flex items-center gap-2">
        <span :class="orderTypeClass">{{ orderTypeLabel }}</span>
        <span v-if="order.table">Table: {{ order.table.name }}</span>
      </div>
      <span v-if="order.waiter">{{ order.waiter.name }}</span>
    </div>

    <!-- Items List -->
    <div class="p-4 max-h-64 overflow-y-auto">
      <div 
        v-for="item in order.items" 
        :key="item.id" 
        :class="[
          'flex items-start gap-3 py-2 border-b border-white/10 last:border-0',
          item.status === 'ready' ? 'opacity-50 line-through' : ''
        ]"
      >
        <button 
          @click="toggleItemReady(item)" 
          :class="[
            'w-8 h-8 rounded-lg flex items-center justify-center transition',
            item.status === 'ready' 
              ? 'bg-green-500 text-white' 
              : 'bg-white/20 hover:bg-white/30'
          ]"
        >
          <i v-if="item.status === 'ready'" class="fas fa-check w-5 h-5"></i>
          <span v-else class="font-bold">{{ item.quantity }}</span>
        </button>
        
        <div class="flex-1">
          <p class="font-medium text-lg">{{ item.item_name }}</p>
          <div v-if="item.modifiers?.length" class="text-sm text-amber-300">
            <span v-for="mod in item.modifiers" :key="mod.id" class="mr-2">
              + {{ mod.modifier_name }}
            </span>
          </div>
          <p v-if="item.notes" class="text-sm text-red-300 mt-1 font-medium">
            ⚠ {{ item.notes }}
          </p>
        </div>
      </div>
    </div>

    <!-- Kitchen Notes -->
    <div v-if="order.kitchen_notes" class="px-4 py-2 bg-red-900/50 text-red-200">
      <p class="text-sm font-medium">📝 {{ order.kitchen_notes }}</p>
    </div>

    <!-- Actions -->
    <div class="p-4 flex gap-2">
      <template v-if="order.status === 'sent_to_kitchen'">
        <button 
          @click="$emit('acknowledge', order)" 
          class="flex-1 py-3 bg-blue-500 hover:bg-blue-600 rounded-lg font-bold text-lg transition"
        >
          START
        </button>
      </template>
      
      <template v-else-if="order.status === 'preparing'">
        <button 
          @click="$emit('ready', order)" 
          class="flex-1 py-3 bg-green-500 hover:bg-green-600 rounded-lg font-bold text-lg transition"
        >
          READY
        </button>
      </template>
      
      <template v-else-if="order.status === 'ready'">
        <button 
          @click="$emit('recall', order)" 
          class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 rounded-lg font-bold text-lg transition"
        >
          RECALL
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  order: { type: Object, required: true },
  elapsedTime: { type: Object, required: true }
})

const emit = defineEmits(['acknowledge', 'start', 'ready', 'item-ready', 'recall'])

// Time thresholds in minutes
const WARNING_TIME = 10
const URGENT_TIME = 15

const isWarning = computed(() => props.elapsedTime.minutes >= WARNING_TIME)
const isUrgent = computed(() => props.elapsedTime.minutes >= URGENT_TIME)

const cardClass = computed(() => {
  if (props.order.status === 'ready') return 'bg-green-800 border-green-700'
  if (isUrgent.value) return 'bg-red-900 border-red-800 animate-pulse-slow'
  if (isWarning.value) return 'bg-amber-900 border-amber-800'
  if (props.order.status === 'preparing') return 'bg-blue-900 border-blue-800'
  return 'bg-gray-800 border-gray-700'
})

const headerClass = computed(() => {
  if (props.order.status === 'ready') return 'bg-green-700'
  if (isUrgent.value) return 'bg-red-800'
  if (isWarning.value) return 'bg-amber-800'
  if (props.order.status === 'preparing') return 'bg-blue-800'
  return 'bg-gray-700'
})

const timerClass = computed(() => {
  if (isUrgent.value) return 'text-red-400'
  if (isWarning.value) return 'text-amber-400'
  return 'text-white'
})

const formatElapsedTime = computed(() => {
  const { minutes, seconds } = props.elapsedTime
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const formatOrderTime = computed(() => {
  return new Date(props.order.sent_to_kitchen_at || props.order.created_at)
    .toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
})

const orderTypeLabel = computed(() => {
  const labels = { dine_in: 'DINE IN', takeaway: 'TAKEAWAY', delivery: 'DELIVERY' }
  return labels[props.order.order_type] || props.order.order_type
})

const orderTypeClass = computed(() => {
  const classes = {
    dine_in: 'px-2 py-1 bg-blue-500 rounded text-xs font-bold text-white',
    takeaway: 'px-2 py-1 bg-amber-500 rounded text-xs font-bold text-black',
    delivery: 'px-2 py-1 bg-purple-500 rounded text-xs font-bold text-white'
  }
  return classes[props.order.order_type] || 'px-2 py-1 bg-gray-500 rounded text-xs font-bold text-white'
})

function toggleItemReady(item) {
  const newStatus = item.status === 'ready' ? 'preparing' : 'ready'
  emit('item-ready', props.order.id, item.id, newStatus)
}
</script>

<style scoped>
.animate-pulse-slow {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-pulse-subtle {
  animation: pulse-subtle 3s ease-in-out infinite;
}

@keyframes pulse-subtle {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.9; }
}
</style>