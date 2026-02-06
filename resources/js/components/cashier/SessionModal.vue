<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
      <!-- Header -->
      <div class="bg-blue-500 text-white p-4 rounded-t-xl">
        <h2 class="text-xl font-bold">
          {{ session ? 'Session Details' : 'Open POS Session' }}
        </h2>
      </div>

      <!-- Open Session Form -->
      <div v-if="!session" class="p-6">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Terminal</label>
          <select v-model="selectedTerminal" class="w-full p-3 border rounded-lg">
            <option v-for="terminal in terminals" :key="terminal.id" :value="terminal.id">
              {{ terminal.name }} ({{ terminal.terminal_code }})
            </option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Opening Cash</label>
          <input 
            v-model.number="openingCash" 
            type="number" 
            step="0.01" 
            class="w-full p-3 border rounded-lg text-lg" 
            placeholder="0.00" 
          />
        </div>

        <div class="flex gap-3">
          <button 
            @click="$emit('close')" 
            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50" 
          >
            Cancel
          </button>
          <button 
            @click="openSession" 
            :disabled="!canOpen" 
            class="flex-1 px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50" 
          >
            Open Session
          </button>
        </div>
      </div>

      <!-- Session Info -->
      <div v-else class="p-6">
        <div class="space-y-4 mb-6">
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Terminal</span>
            <span class="font-medium">{{ session.terminal?.name }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Opened At</span>
            <span class="font-medium">{{ formatDateTime(session.opened_at) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Opening Cash</span>
            <span class="font-medium">{{ formatCurrency(session.opening_cash) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Total Sales</span>
            <span class="font-medium text-green-600">{{ formatCurrency(session.total_sales) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Cash Sales</span>
            <span class="font-medium">{{ formatCurrency(session.total_cash_sales) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Card Sales</span>
            <span class="font-medium">{{ formatCurrency(session.total_card_sales) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">Expected Cash</span>
            <span class="font-medium">{{ formatCurrency(expectedCash) }}</span>
          </div>
        </div>

        <!-- Close Session -->
        <div v-if="showCloseForm" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Actual Closing Cash</label>
          <input 
            v-model.number="closingCash" 
            type="number" 
            step="0.01" 
            class="w-full p-3 border rounded-lg text-lg" 
            placeholder="0.00" 
          />
          <div v-if="cashDifference !== 0" class="mt-2">
            <span :class="cashDifference > 0 ? 'text-green-600' : 'text-red-600'">
              Difference: {{ formatCurrency(cashDifference) }}
              ({{ cashDifference > 0 ? 'Over' : 'Short' }})
            </span>
          </div>
          <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea 
              v-model="closeNotes" 
              rows="2" 
              class="w-full p-3 border rounded-lg" 
              placeholder="Optional notes..." 
            ></textarea>
          </div>
        </div>

        <div class="flex gap-3">
          <button 
            @click="$emit('close')" 
            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50" 
          >
            Close
          </button>
          <button 
            v-if="!showCloseForm" 
            @click="showCloseForm = true" 
            class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600" 
          >
            Close Session
          </button>
          <button 
            v-else 
            @click="closeSession" 
            class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600" 
          >
            Confirm Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const props = defineProps({
  session: { type: Object, default: null }
})

const emit = defineEmits(['close', 'open', 'close-session'])

const authStore = useAuthStore()

const terminals = ref([])
const selectedTerminal = ref(null)
const openingCash = ref(0)
const closingCash = ref(0)
const closeNotes = ref('')
const showCloseForm = ref(false)

const canOpen = computed(() => selectedTerminal.value && openingCash.value >= 0)

const expectedCash = computed(() => {
  if (!props.session) return 0
  return parseFloat(props.session.opening_cash) + parseFloat(props.session.total_cash_sales)
})

const cashDifference = computed(() => {
  return closingCash.value - expectedCash.value
})

function formatCurrency(value) {
  return `Rs. ${(value || 0).toLocaleString()}`
}

function formatDateTime(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

async function loadTerminals() {
  try {
    const response = await api.get('/terminals', {
      params: { branch_id: authStore.branch?.id, type: 'cashier' }
    })
    terminals.value = response.data.data
    if (terminals.value.length > 0) {
      selectedTerminal.value = terminals.value[0].id
    }
  } catch (error) {
    console.error('Failed to load terminals:', error)
  }
}

function openSession() {
  emit('open', {
    terminal_id: selectedTerminal.value,
    opening_cash: openingCash.value
  })
}

function closeSession() {
  emit('close-session', {
    closing_cash: closingCash.value,
    notes: closeNotes.value
  })
}

onMounted(() => {
  if (!props.session) {
    loadTerminals()
  }
})
</script>