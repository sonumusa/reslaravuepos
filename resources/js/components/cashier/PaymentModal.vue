<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-4">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="text-xl font-bold">Process Payment</h2>
            <p class="text-green-100">Order #{{ order.order_number }}</p>
          </div>
          <button @click="$emit('close')" class="p-2 hover:bg-white/20 rounded-full">
            <i class="fas fa-times w-6 h-6 flex items-center justify-center text-xl"></i>
          </button>
        </div>
      </div>

      <div class="flex">
        <!-- Left: Amount & Methods -->
        <div class="w-1/2 p-6 border-r">
          <!-- Amount Display -->
          <div class="text-center mb-6 p-4 bg-gray-50 rounded-xl">
            <p class="text-sm text-gray-500 mb-1">Total Amount</p>
            <p class="text-4xl font-bold text-gray-800">{{ formatCurrency(total) }}</p>
          </div>

          <!-- Payment Methods -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
            <div class="grid grid-cols-3 gap-3">
              <button 
                v-for="method in paymentMethods" 
                :key="method.value" 
                @click="selectedMethod = method.value" 
                :class="[
                  'p-4 rounded-lg border-2 text-center transition',
                  selectedMethod === method.value
                    ? 'border-green-500 bg-green-50'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <i :class="[method.icon, 'w-8 h-8 mx-auto mb-2 text-2xl block']"></i>
                <span class="text-sm font-medium">{{ method.label }}</span>
              </button>
            </div>
          </div>

          <!-- Split Payment -->
          <div v-if="selectedMethod === 'split'" class="mb-6 space-y-3">
            <div class="flex gap-3">
              <div class="flex-1">
                <label class="text-sm text-gray-600">Cash Amount</label>
                <input 
                  v-model.number="splitCash" 
                  type="number" 
                  class="w-full p-2 border rounded-lg" 
                  @input="calculateSplit" 
                />
              </div>
              <div class="flex-1">
                <label class="text-sm text-gray-600">Card Amount</label>
                <input 
                  v-model.number="splitCard" 
                  type="number" 
                  class="w-full p-2 border rounded-lg" 
                  readonly 
                />
              </div>
            </div>
          </div>

          <!-- Card Details -->
          <div v-if="selectedMethod === 'card'" class="mb-6">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-sm text-gray-600">Card Type</label>
                <select v-model="cardType" class="w-full p-2 border rounded-lg">
                  <option value="visa">Visa</option>
                  <option value="mastercard">Mastercard</option>
                  <option value="amex">American Express</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Last 4 Digits</label>
                <input 
                  v-model="cardLastFour" 
                  type="text" 
                  maxlength="4" 
                  placeholder="0000" 
                  class="w-full p-2 border rounded-lg" 
                />
              </div>
            </div>
            <div class="mt-3">
              <label class="text-sm text-gray-600">Transaction Reference</label>
              <input 
                v-model="transactionRef" 
                type="text" 
                placeholder="Optional" 
                class="w-full p-2 border rounded-lg" 
              />
            </div>
          </div>

          <!-- Tip -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Add Tip</label>
            <div class="flex gap-2">
              <button 
                v-for="tipOption in tipOptions" 
                :key="tipOption" 
                @click="tipAmount = total * (tipOption / 100)" 
                :class="[
                  'px-3 py-2 rounded-lg text-sm transition',
                  tipAmount === total * (tipOption / 100)
                    ? 'bg-amber-500 text-white'
                    : 'bg-gray-100 hover:bg-gray-200'
                ]"
              >
                {{ tipOption }}%
              </button>
              <input 
                v-model.number="tipAmount" 
                type="number" 
                placeholder="Custom" 
                class="w-24 p-2 border rounded-lg text-sm" 
              />
            </div>
          </div>
        </div>

        <!-- Right: Numpad & Summary -->
        <div class="w-1/2 p-6">
          <!-- Cash Tendered -->
          <div v-if="selectedMethod === 'cash' || selectedMethod === 'split'" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Cash Tendered</label>
            <input 
              v-model="tenderedDisplay" 
              type="text" 
              readonly 
              class="w-full p-4 text-2xl text-right font-mono border-2 rounded-lg" 
            />
          </div>

          <!-- Numpad -->
          <div v-if="selectedMethod === 'cash' || selectedMethod === 'split'" class="grid grid-cols-3 gap-2 mb-4">
            <button 
              v-for="num in ['1','2','3','4','5','6','7','8','9','.','0','⌫']" 
              :key="num" 
              @click="handleNumpad(num)" 
              class="p-4 text-xl font-medium bg-gray-100 hover:bg-gray-200 rounded-lg transition" 
            >
              {{ num }}
            </button>
          </div>

          <!-- Quick Cash Buttons -->
          <div v-if="selectedMethod === 'cash'" class="grid grid-cols-4 gap-2 mb-6">
            <button 
              v-for="amount in quickAmounts" 
              :key="amount" 
              @click="tendered = amount" 
              class="p-2 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100" 
            >
              {{ formatCurrency(amount) }}
            </button>
          </div>

          <!-- Summary -->
          <div class="bg-gray-50 rounded-xl p-4 mb-4">
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span>Subtotal</span>
                <span>{{ formatCurrency(total) }}</span>
              </div>
              <div v-if="tipAmount > 0" class="flex justify-between text-amber-600">
                <span>Tip</span>
                <span>{{ formatCurrency(tipAmount) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold border-t pt-2">
                <span>Grand Total</span>
                <span>{{ formatCurrency(grandTotal) }}</span>
              </div>
              <div v-if="change > 0" class="flex justify-between text-green-600 font-bold text-xl">
                <span>Change</span>
                <span>{{ formatCurrency(change) }}</span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3">
            <button 
              @click="$emit('close')" 
              class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50" 
            >
              Cancel
            </button>
            <button 
              @click="processPayment" 
              :disabled="!canProcess" 
              :class="[
                'flex-1 px-4 py-3 rounded-lg font-medium transition',
                canProcess
                  ? 'bg-green-500 text-white hover:bg-green-600'
                  : 'bg-gray-300 text-gray-500 cursor-not-allowed'
              ]"
            >
              Complete Payment
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  order: { type: Object, required: true },
  total: { type: Number, required: true }
})

const emit = defineEmits(['close', 'complete'])

const selectedMethod = ref('cash')
const tendered = ref(0)
const tipAmount = ref(0)
const cardType = ref('visa')
const cardLastFour = ref('')
const transactionRef = ref('')
const splitCash = ref(0)
const splitCard = ref(0)

const paymentMethods = [
  { value: 'cash', label: 'Cash', icon: 'fas fa-money-bill' },
  { value: 'card', label: 'Card', icon: 'fas fa-credit-card' },
  { value: 'split', label: 'Split', icon: 'fas fa-columns' },
]

const tipOptions = [0, 5, 10, 15, 20]
const quickAmounts = computed(() => {
  const amounts = [100, 500, 1000, 5000]
  const roundedTotal = Math.ceil(props.total / 100) * 100
  if (!amounts.includes(roundedTotal)) {
    amounts.unshift(roundedTotal)
  }
  return amounts.slice(0, 4)
})

const grandTotal = computed(() => props.total + tipAmount.value)
const change = computed(() => {
  if (selectedMethod.value !== 'cash') return 0
  return Math.max(0, tendered.value - grandTotal.value)
})

const tenderedDisplay = computed(() => formatCurrency(tendered.value))

const canProcess = computed(() => {
  if (selectedMethod.value === 'cash') {
    return tendered.value >= grandTotal.value
  }
  if (selectedMethod.value === 'card') {
    return cardLastFour.value.length === 4
  }
  if (selectedMethod.value === 'split') {
    return (splitCash.value + splitCard.value) >= grandTotal.value
  }
  return true
})

function formatCurrency(value) {
  return `Rs. ${(value || 0).toLocaleString()}`
}

function handleNumpad(key) {
  if (key === '⌫') {
    tendered.value = Math.floor(tendered.value / 10)
  } else if (key === '.') {
    // Handle decimal - simplified for now
  } else {
    tendered.value = tendered.value * 10 + parseInt(key)
  }
}

function calculateSplit() {
  splitCard.value = Math.max(0, grandTotal.value - splitCash.value)
}

function processPayment() {
  const paymentData = {
    method: selectedMethod.value,
    amount: props.total,
    tip: tipAmount.value,
    tendered: tendered.value,
    change: change.value,
    card_type: selectedMethod.value === 'card' ? cardType.value : null,
    card_last_four: selectedMethod.value === 'card' ? cardLastFour.value : null,
    transaction_reference: transactionRef.value || null,
    split_cash: selectedMethod.value === 'split' ? splitCash.value : null,
    split_card: selectedMethod.value === 'split' ? splitCard.value : null,
  }
  
  emit('complete', paymentData)
}
</script>