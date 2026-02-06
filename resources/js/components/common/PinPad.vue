<template> 
    <div class="pinpad"> 
        <!-- PIN Display --> 
        <div class="flex justify-center gap-3 mb-6"> 
            <div 
                v-for="i in pinLength" 
                :key="i" 
                :class="[ 
                    'w-12 h-12 rounded-xl flex items-center justify-center text-2xl font-bold transition-all', 
                    i <= pin.length ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-500' 
                ]" 
            > 
                {{ i <= pin.length ? '•' : '' }} 
            </div> 
        </div> 
        
        <!-- Error Message --> 
        <p v-if="error" class="text-red-400 text-sm text-center mb-4"> 
            <i class="fas fa-exclamation-circle mr-1"></i> 
            {{ error }} 
        </p> 
        
        <!-- Number Grid --> 
        <div class="grid grid-cols-3 gap-3"> 
            <button 
                v-for="n in [1,2,3,4,5,6,7,8,9]" 
                :key="n" 
                @click="addDigit(n)" 
                :disabled="loading" 
                class="bg-slate-700 hover:bg-slate-600 active:bg-slate-500 rounded-xl py-5 text-2xl font-bold transition-colors disabled:opacity-50 text-white" 
            > 
                {{ n }} 
            </button> 
            
            <button 
                @click="clear" 
                :disabled="loading" 
                class="bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-xl py-5 transition-colors disabled:opacity-50" 
            > 
                <i class="fas fa-times text-xl"></i> 
            </button> 
            
            <button 
                @click="addDigit(0)" 
                :disabled="loading" 
                class="bg-slate-700 hover:bg-slate-600 active:bg-slate-500 rounded-xl py-5 text-2xl font-bold transition-colors disabled:opacity-50 text-white" 
            > 
                0 
            </button> 
            
            <button 
                @click="submit" 
                :disabled="!isComplete || loading" 
                :class="[ 
                    'rounded-xl py-5 transition-colors text-white', 
                    isComplete 
                        ? 'bg-green-600 hover:bg-green-500' 
                        : 'bg-slate-600 opacity-50' 
                ]" 
            > 
                <i v-if="loading" class="fas fa-spinner fa-spin text-xl"></i> 
                <i v-else class="fas fa-arrow-right text-xl"></i> 
            </button> 
        </div> 
    </div> 
</template> 

<script setup> 
import { ref, computed, watch } from 'vue'; 

const props = defineProps({ 
    pinLength: { 
        type: Number, 
        default: 4 
    }, 
    error: { 
        type: String, 
        default: '' 
    }, 
    loading: { 
        type: Boolean, 
        default: false 
    }, 
    autoSubmit: { 
        type: Boolean, 
        default: true 
    } 
}); 

const emit = defineEmits(['submit', 'change']); 

const pin = ref(''); 

const isComplete = computed(() => pin.value.length === props.pinLength); 

watch(isComplete, (complete) => { 
    if (complete && props.autoSubmit) { 
        submit(); 
    } 
}); 

function addDigit(digit) { 
    if (pin.value.length < props.pinLength && !props.loading) { 
        pin.value += String(digit); 
        emit('change', pin.value); 
    } 
} 

function clear() { 
    pin.value = ''; 
    emit('change', pin.value); 
} 

function backspace() { 
    pin.value = pin.value.slice(0, -1); 
    emit('change', pin.value); 
} 

function submit() { 
    if (isComplete.value && !props.loading) { 
        emit('submit', pin.value); 
    } 
} 

function reset() { 
    pin.value = ''; 
} 

// Expose methods 
defineExpose({ reset, clear }); 
</script>
