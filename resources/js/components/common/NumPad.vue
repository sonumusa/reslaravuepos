<template> 
    <div class="numpad"> 
        <!-- Display --> 
        <div v-if="showDisplay" class="mb-4"> 
            <div class="bg-slate-700 rounded-xl px-4 py-3 text-right"> 
                <span v-if="label" class="text-xs text-slate-400 block">{{ label }}</span> 
                <span class="text-2xl font-bold text-white">{{ prefix }}{{ displayValue }}</span> 
            </div> 
        </div> 
        
        <!-- Number Grid --> 
        <div class="grid grid-cols-3 gap-2"> 
            <button 
                v-for="n in [1,2,3,4,5,6,7,8,9]" 
                :key="n" 
                @click="addDigit(n)" 
                class="bg-slate-700 hover:bg-slate-600 rounded-xl py-4 text-xl font-bold transition-colors text-white active:bg-slate-500 active:scale-95 transform duration-100" 
            > 
                {{ n }} 
            </button> 
            
            <!-- Bottom Row --> 
            <button 
                v-if="showDecimal" 
                @click="addDecimal" 
                class="bg-slate-700 hover:bg-slate-600 rounded-xl py-4 text-xl font-bold transition-colors text-white active:bg-slate-500 active:scale-95 transform duration-100" 
                :disabled="hasDecimal" 
            > 
                . 
            </button> 
            <button 
                v-else 
                @click="addDigit('00')" 
                class="bg-slate-700 hover:bg-slate-600 rounded-xl py-4 text-xl font-bold transition-colors text-white active:bg-slate-500 active:scale-95 transform duration-100" 
            > 
                00 
            </button> 
            
            <button 
                @click="addDigit(0)" 
                class="bg-slate-700 hover:bg-slate-600 rounded-xl py-4 text-xl font-bold transition-colors text-white active:bg-slate-500 active:scale-95 transform duration-100" 
            > 
                0 
            </button> 
            
            <button 
                @click="backspace" 
                class="bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-xl py-4 transition-colors active:bg-red-500/40 active:scale-95 transform duration-100" 
            > 
                <i class="fas fa-backspace text-xl"></i> 
            </button> 
        </div> 
        
        <!-- Quick Amount Buttons --> 
        <div v-if="showQuickAmounts" class="grid grid-cols-4 gap-2 mt-3"> 
            <button 
                v-for="amount in quickAmounts" 
                :key="amount" 
                @click="setAmount(amount)" 
                class="bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded-lg py-2 text-sm font-medium transition-colors active:scale-95 transform duration-100" 
            > 
                +{{ formatAmount(amount) }} 
            </button> 
        </div> 
        
        <!-- Action Buttons --> 
        <div v-if="showActions" class="grid grid-cols-2 gap-3 mt-4"> 
            <button 
                @click="clear" 
                class="bg-slate-600 hover:bg-slate-500 text-white rounded-xl py-3 font-medium transition-colors" 
            > 
                Clear 
            </button> 
            <button 
                @click="submit" 
                class="bg-green-600 hover:bg-green-500 text-white rounded-xl py-3 font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                :disabled="!isValid" 
            > 
                {{ submitText }} 
            </button> 
        </div> 
    </div> 
</template> 

<script setup> 
import { ref, computed, watch } from 'vue'; 

const props = defineProps({ 
    modelValue: { 
        type: [String, Number], 
        default: '' 
    }, 
    label: String, 
    prefix: { 
        type: String, 
        default: 'Rs. ' 
    }, 
    showDisplay: { 
        type: Boolean, 
        default: true 
    }, 
    showDecimal: { 
        type: Boolean, 
        default: false 
    }, 
    showQuickAmounts: { 
        type: Boolean, 
        default: false 
    }, 
    quickAmounts: { 
        type: Array, 
        default: () => [500, 1000, 2000, 5000] 
    }, 
    showActions: { 
        type: Boolean, 
        default: false 
    }, 
    submitText: { 
        type: String, 
        default: 'Submit' 
    }, 
    maxLength: { 
        type: Number, 
        default: 10 
    }, 
    maxValue: { 
        type: Number, 
        default: Infinity 
    }, 
    minValue: { 
        type: Number, 
        default: 0 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'submit', 'change']); 

const internalValue = ref(String(props.modelValue || '')); 

const displayValue = computed(() => { 
    if (!internalValue.value) return '0'; 
    return Number(internalValue.value).toLocaleString(); 
}); 

const hasDecimal = computed(() => internalValue.value.includes('.')); 

const isValid = computed(() => { 
    const num = Number(internalValue.value); 
    return num >= props.minValue && num <= props.maxValue; 
}); 

watch(() => props.modelValue, (newVal) => { 
    internalValue.value = String(newVal || ''); 
}); 

function addDigit(digit) { 
    const newValue = internalValue.value + String(digit); 
    if (newValue.length <= props.maxLength && Number(newValue) <= props.maxValue) { 
        internalValue.value = newValue; 
        emitChange(); 
    } 
} 

function addDecimal() { 
    if (!hasDecimal.value && internalValue.value.length < props.maxLength) { 
        internalValue.value = (internalValue.value || '0') + '.'; 
        emitChange(); 
    } 
} 

function backspace() { 
    internalValue.value = internalValue.value.slice(0, -1); 
    emitChange(); 
} 

function clear() { 
    internalValue.value = ''; 
    emitChange(); 
} 

function setAmount(amount) { 
    const current = Number(internalValue.value) || 0; 
    const newValue = current + amount; 
    if (newValue <= props.maxValue) { 
        internalValue.value = String(newValue); 
        emitChange(); 
    } 
} 

function formatAmount(amount) { 
    return amount >= 1000 ? `${amount / 1000}k` : amount; 
} 

function emitChange() { 
    emit('update:modelValue', internalValue.value); 
    emit('change', Number(internalValue.value) || 0); 
} 

function submit() { 
    if (isValid.value) { 
        emit('submit', Number(internalValue.value) || 0); 
    } 
} 

// Expose methods 
defineExpose({ clear, setAmount }); 
</script>
