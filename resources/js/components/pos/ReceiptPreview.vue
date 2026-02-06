<template>
  <div class="bg-white text-slate-900 p-6 rounded-lg max-w-sm mx-auto text-sm">
    <!-- Header -->
    <div class="text-center mb-4">
      <h2 class="text-xl font-bold">🍽️ {{ branchName }}</h2>
      <p class="text-slate-600">{{ branchAddress }}</p>
      <p class="text-slate-600">Tel: {{ branchPhone }}</p>
    </div>
    
    <div class="border-t border-dashed border-slate-300 my-3"></div>
    
    <!-- Invoice Info -->
    <div class="space-y-1 mb-3">
      <div class="flex justify-between">
        <span class="text-slate-600">Invoice #:</span>
        <span class="font-mono">{{ invoice.invoice_number }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-600">Date:</span>
        <span>{{ formatDate(invoice.created_at) }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-600">Table:</span>
        <span>{{ invoice.order?.table?.name || 'N/A' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-600">Cashier:</span>
        <span>{{ invoice.cashier?.name || 'N/A' }}</span>
      </div>
    </div>
    
    <div class="border-t border-dashed border-slate-300 my-3"></div>
    
    <!-- Items -->
    <div class="space-y-2 mb-3">
      <div v-for="item in invoice.items" :key="item.id" class="flex justify-between">
        <div>
          <span class="font-medium">{{ item.item_name }}</span>
          <span class="text-slate-600 text-xs block">x{{ item.quantity }}</span>
        </div>
        <span>Rs. {{ item.subtotal?.toLocaleString() }}</span>
      </div>
    </div>
    
    <div class="border-t border-dashed border-slate-300 my-3"></div>
    
    <!-- Totals -->
    <div class="space-y-1">
      <div class="flex justify-between">
        <span class="text-slate-600">Subtotal:</span>
        <span>Rs. {{ invoice.subtotal?.toLocaleString() }}</span>
      </div>
      <div v-if="invoice.discount_amount" class="flex justify-between text-green-600">
        <span>Discount:</span>
        <span>- Rs. {{ invoice.discount_amount?.toLocaleString() }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-600">GST ({{ invoice.tax_rate }}%):</span>
        <span>Rs. {{ invoice.tax_amount?.toLocaleString() }}</span>
      </div>
      <div class="flex justify-between font-bold text-lg pt-2 border-t border-slate-300">
        <span>Total:</span>
        <span>Rs. {{ invoice.total_amount?.toLocaleString() }}</span>
      </div>
    </div>
    
    <div class="border-t border-dashed border-slate-300 my-3"></div>
    
    <!-- Payment Info -->
    <div class="space-y-1 mb-4">
      <div class="flex justify-between">
        <span class="text-slate-600">Payment:</span>
        <span class="capitalize">{{ invoice.payments?.[0]?.method || 'Cash' }}</span>
      </div>
      <div v-if="invoice.payments?.[0]?.tendered" class="flex justify-between">
        <span class="text-slate-600">Tendered:</span>
        <span>Rs. {{ invoice.payments[0].tendered?.toLocaleString() }}</span>
      </div>
      <div v-if="invoice.change_amount" class="flex justify-between">
        <span class="text-slate-600">Change:</span>
        <span>Rs. {{ invoice.change_amount?.toLocaleString() }}</span>
      </div>
    </div>
    
    <!-- PRA Section -->
    <div 
      :class="[
        'rounded-lg p-3 text-center mb-4',
        invoice.pra_status === 'success'
          ? 'bg-green-50 border border-green-200'
          : 'bg-amber-50 border border-amber-200'
      ]"
    >
      <p 
        :class="[
          'text-xs font-medium mb-1',
          invoice.pra_status === 'success' ? 'text-green-700' : 'text-amber-700'
        ]"
      >
        {{ invoice.pra_status === 'success'
          ? '✓ TAX INVOICE - PRA VERIFIED'
          : '⚠ OFFLINE INVOICE - PRA SUBMISSION PENDING'
        }}
      </p>
      <p v-if="invoice.pra_invoice_number" class="text-xs text-slate-600">
        PRA #: {{ invoice.pra_invoice_number }}
      </p>
      
      <!-- QR Code Placeholder -->
      <div v-if="invoice.pra_qr_code" class="mt-2 flex justify-center">
        <div class="w-20 h-20 bg-slate-200 flex items-center justify-center text-xs text-slate-500">
          [QR Code]
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="text-center text-xs mt-6">
      <p class="text-slate-500 mb-2">Thank you for dining with us!</p>
      <p v-if="ntnNumber" class="text-slate-400 mt-1">NTN: {{ ntnNumber }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  invoice: {
    type: Object,
    required: true
  }
});

const authStore = useAuthStore();

const branchName = computed(() => authStore.branch?.name || 'Restaurant');
const branchAddress = computed(() => authStore.branch?.address || '');
const branchPhone = computed(() => authStore.branch?.phone || '');
const ntnNumber = computed(() => authStore.branch?.ntn_number || '');

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>
