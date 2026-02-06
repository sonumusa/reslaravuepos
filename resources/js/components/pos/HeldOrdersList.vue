<template>
  <Modal v-model="isOpen" title="Held Orders" size="lg">
    <div class="space-y-4">
      <div v-if="orders.length === 0" class="text-center py-12 text-slate-500">
        <i class="fas fa-pause-circle text-4xl mb-3 opacity-50"></i>
        <p>No held orders</p>
      </div>

      <div v-else class="grid gap-3">
        <div 
          v-for="order in orders" 
          :key="order.uuid"
          class="bg-slate-800 border border-slate-700 rounded-xl p-4 flex items-center justify-between group hover:border-slate-600 transition-colors"
        >
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
              <span class="font-bold text-lg text-white">{{ order.table_name || 'No Table' }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                {{ formatTime(order.created_at) }}
              </span>
            </div>
            <div class="text-sm text-slate-400">
              {{ order.items.length }} items • {{ formatCurrency(calculateTotal(order)) }}
            </div>
            <div v-if="order.notes" class="text-xs text-slate-500 mt-1 italic">
              "{{ order.notes }}"
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button 
              @click="$emit('delete', order)"
              class="p-3 text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"
              title="Delete Order"
            >
              <i class="fas fa-trash"></i>
            </button>
            <button 
              @click="$emit('resume', order.uuid)"
              class="btn-primary py-2 px-4 text-sm"
            >
              Resume
            </button>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <button @click="isOpen = false" class="btn-secondary">Close</button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { computed } from 'vue';
import Modal from '@/components/common/Modal.vue';
import { useSettingsStore } from '@/stores/settings';

const props = defineProps({
  modelValue: Boolean,
  orders: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'resume', 'delete']);
const settingsStore = useSettingsStore();

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

function formatTime(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatCurrency(amount) {
  return settingsStore.formatCurrency(amount);
}

function calculateTotal(order) {
  if (!order || !order.items) return 0;
  
  const subtotal = order.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
  
  // Simple calculation for display (ignoring complex tax/discount logic here for brevity, 
  // as exact total should probably be stored or computed by store)
  return subtotal;
}
</script>
