<template>
  <Modal v-model="isOpen" title="Apply Discount" size="sm">
    <div class="space-y-4">
      <!-- Discount Type -->
      <div class="flex bg-slate-700 rounded-lg p-1">
        <button 
          v-for="type in ['percentage', 'fixed']" 
          :key="type"
          @click="discountType = type"
          class="flex-1 py-2 rounded-md text-sm font-medium transition-colors"
          :class="discountType === type ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white'"
        >
          {{ type === 'percentage' ? 'Percentage (%)' : 'Fixed Amount' }}
        </button>
      </div>

      <!-- Value Input -->
      <div>
        <label class="block text-sm font-medium text-slate-400 mb-1">
          {{ discountType === 'percentage' ? 'Percentage' : 'Amount' }}
        </label>
        <div class="relative">
          <input 
            type="number" 
            v-model="discountValue" 
            class="input-field pl-10 text-xl font-bold"
            placeholder="0"
            min="0"
          >
          <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">
            {{ discountType === 'percentage' ? '%' : '$' }}
          </div>
        </div>
      </div>

      <!-- Reason -->
      <div>
        <label class="block text-sm font-medium text-slate-400 mb-1">Reason (Optional)</label>
        <input 
          type="text" 
          v-model="discountReason" 
          class="input-field"
          placeholder="e.g. Employee Meal, VIP"
        >
      </div>

      <!-- Current Discount Info -->
      <div v-if="currentDiscount" class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-3 text-sm text-blue-400">
        Current: {{ currentDiscount.type === 'percentage' ? currentDiscount.value + '%' : '$' + currentDiscount.value }} 
        ({{ currentDiscount.reason || 'No reason' }})
      </div>
    </div>

    <template #footer>
      <div class="flex gap-3">
        <button 
          v-if="currentDiscount"
          @click="$emit('clear'); isOpen = false" 
          class="px-4 py-2 border border-red-500/30 text-red-400 rounded-lg hover:bg-red-500/10"
        >
          Remove
        </button>
        <div class="flex-1"></div>
        <button @click="isOpen = false" class="btn-secondary">Cancel</button>
        <button 
          @click="apply" 
          class="btn-primary"
          :disabled="!isValid"
        >
          Apply Discount
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/components/common/Modal.vue';

const props = defineProps({
  modelValue: Boolean,
  currentDiscount: Object
});

const emit = defineEmits(['update:modelValue', 'apply', 'clear']);

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const discountType = ref('percentage');
const discountValue = ref('');
const discountReason = ref('');

const isValid = computed(() => {
  const val = parseFloat(discountValue.value);
  return !isNaN(val) && val > 0;
});

function apply() {
  if (!isValid.value) return;
  
  emit('apply', {
    type: discountType.value,
    value: parseFloat(discountValue.value),
    reason: discountReason.value
  });
  
  isOpen.value = false;
  resetForm();
}

function resetForm() {
  discountType.value = 'percentage';
  discountValue.value = '';
  discountReason.value = '';
}

watch(() => props.currentDiscount, (newVal) => {
  if (newVal) {
    discountType.value = newVal.type;
    discountValue.value = newVal.value;
    discountReason.value = newVal.reason || '';
  } else {
    resetForm();
  }
}, { immediate: true });
</script>
