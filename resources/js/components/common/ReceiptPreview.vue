<template>
  <div class="receipt-preview bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
    <!-- Header -->
    <div class="text-center border-b-2 border-dashed border-gray-300 pb-4 mb-4">
      <h2 class="text-xl font-bold">{{ branch?.name || 'Restaurant' }}</h2>
      <p class="text-sm text-gray-600">{{ branch?.address }}</p>
      <p class="text-sm text-gray-600">{{ branch?.city }}</p>
      <p class="text-sm text-gray-600">Phone: {{ branch?.phone }}</p>
      <p class="text-xs text-gray-500">NTN: {{ branch?.ntn_number }}</p>
    </div>

    <!-- Invoice Info -->
    <div class="text-sm space-y-1 mb-4">
      <div class="flex justify-between">
        <span class="font-semibold">Invoice:</span>
        <span>{{ invoice.invoice_number }}</span>
      </div>
      <div class="flex justify-between">
        <span>Date:</span>
        <span>{{ formatDateTime(invoice.created_at) }}</span>
      </div>
      <div v-if="invoice.order?.table" class="flex justify-between">
        <span>Table:</span>
        <span>{{ invoice.order.table.name }}</span>
      </div>
    </div>

    <!-- Items -->
    <div class="border-t border-b border-dashed border-gray-300 py-3 mb-3">
      <div v-for="item in invoice.items" :key="item.id" class="mb-3">
        <div class="flex justify-between font-medium">
          <span>{{ item.item_name }}</span>
          <span>{{ formatCurrency(item.subtotal) }}</span>
        </div>
        <div class="flex justify-between text-sm text-gray-600">
          <span>{{ item.quantity }} x {{ formatCurrency(item.unit_price) }}</span>
        </div>
        <div v-if="item.modifiers?.length" class="text-xs text-gray-500 ml-2">
          <span v-for="mod in item.modifiers" :key="mod.id" class="mr-2">
            + {{ mod.modifier_name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Totals -->
    <div class="space-y-2 text-sm">
      <div class="flex justify-between">
        <span>Subtotal:</span>
        <span>{{ formatCurrency(invoice.subtotal) }}</span>
      </div>
      <div v-if="invoice.discount_amount > 0" class="flex justify-between text-green-600">
        <span>Discount:</span>
        <span>-{{ formatCurrency(invoice.discount_amount) }}</span>
      </div>
      <div class="flex justify-between">
        <span>Tax ({{ invoice.tax_rate }}%):</span>
        <span>{{ formatCurrency(invoice.tax_amount) }}</span>
      </div>
      <div class="flex justify-between text-lg font-bold border-t-2 border-gray-800 pt-2">
        <span>TOTAL:</span>
        <span>{{ formatCurrency(invoice.total_amount) }}</span>
      </div>
    </div>

    <!-- PRA QR Code -->
    <div v-if="invoice.pra_qr_code" class="text-center my-4">
      <img :src="invoice.pra_qr_code" alt="PRA QR" class="w-32 h-32 mx-auto" />
      <p class="text-xs text-gray-500">{{ invoice.pra_fiscal_code }}</p>
    </div>

    <!-- Footer -->
    <div class="text-center text-sm text-gray-600 mt-4 pt-4 border-t border-dashed border-gray-300">
      <p class="font-bold">Thank You! Please Come Again</p>
      <p class="text-xs">Powered by ResLaraVuePOS</p>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-2 mt-4">
      <button 
        @click="handlePrint" 
        class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
      >
        Print Receipt
      </button>
      <button 
        @click="$emit('close')" 
        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
      >
        Close
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatCurrency, formatDateTime } from '@/utils/formatters'
import printService from '@/services/printService'

const props = defineProps({
  invoice: { type: Object, required: true }
})

const emit = defineEmits(['close', 'printed'])

const branch = computed(() => props.invoice.branch)

async function handlePrint() {
  try {
    await printService.print(props.invoice)
    emit('printed')
  } catch (error) {
    console.error('Print failed:', error)
    alert('Failed to print receipt: ' + error.message)
  }
}
</script>
