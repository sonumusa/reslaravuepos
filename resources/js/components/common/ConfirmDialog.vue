<template>
    <Modal 
        v-model="isOpen" 
        :title="title" 
        size="sm" 
        :close-on-backdrop="false" 
        :show-close="false" 
    > 
        <div class="text-center"> 
            <!-- Icon --> 
            <div 
                :class="[ 
                    'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4', 
                    iconBgClass 
                ]" 
            > 
                <i :class="[iconClass, 'text-2xl']"></i> 
            </div> 
            
            <!-- Message --> 
            <p class="text-slate-300 mb-6">{{ message }}</p> 
            
            <!-- Input for confirmation (optional) --> 
            <div v-if="requireInput" class="mb-6"> 
                <label class="text-sm text-slate-400 mb-2 block">{{ inputLabel }}</label> 
                <input 
                    v-model="inputValue" 
                    :type="inputType" 
                    :placeholder="inputPlaceholder" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 text-center" 
                    @keyup.enter="confirm" 
                > 
            </div> 
        </div> 
        
        <template #footer> 
            <div class="flex gap-3"> 
                <button 
                    @click="cancel" 
                    class="flex-1 bg-slate-700 hover:bg-slate-600 text-white rounded-xl py-3 transition-colors" 
                    :disabled="loading" 
                > 
                    {{ cancelText }} 
                </button> 
                <button 
                    @click="confirm" 
                    :class="['flex-1 py-3 font-bold transition-colors', confirmButtonClass]" 
                    :disabled="loading || (requireInput && !inputValue)" 
                > 
                    <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i> 
                    {{ confirmText }} 
                </button> 
            </div> 
        </template> 
    </Modal> 
</template> 

<script setup> 
import { ref, computed } from 'vue'; 
import Modal from './Modal.vue'; 

const props = defineProps({ 
    modelValue: Boolean, 
    type: { 
        type: String, 
        default: 'warning', 
        validator: (v) => ['warning', 'danger', 'info', 'success'].includes(v) 
    }, 
    title: { 
        type: String, 
        default: 'Confirm Action' 
    }, 
    message: { 
        type: String, 
        default: 'Are you sure you want to proceed?' 
    }, 
    confirmText: { 
        type: String, 
        default: 'Confirm' 
    }, 
    cancelText: { 
        type: String, 
        default: 'Cancel' 
    }, 
    requireInput: Boolean, 
    inputLabel: { 
        type: String, 
        default: 'Enter value' 
    }, 
    inputType: { 
        type: String, 
        default: 'text' 
    }, 
    inputPlaceholder: { 
        type: String, 
        default: '' 
    }, 
    loading: Boolean 
}); 

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']); 

const inputValue = ref(''); 

const isOpen = computed({ 
    get: () => props.modelValue, 
    set: (val) => emit('update:modelValue', val) 
}); 

const iconClass = computed(() => { 
    const icons = { 
        warning: 'fas fa-exclamation-triangle text-amber-400', 
        danger: 'fas fa-trash text-red-400', 
        info: 'fas fa-info-circle text-blue-400', 
        success: 'fas fa-check-circle text-green-400' 
    }; 
    return icons[props.type]; 
}); 

const iconBgClass = computed(() => { 
    const bgs = { 
        warning: 'bg-amber-500/20', 
        danger: 'bg-red-500/20', 
        info: 'bg-blue-500/20', 
        success: 'bg-green-500/20' 
    }; 
    return bgs[props.type]; 
}); 

const confirmButtonClass = computed(() => { 
    const classes = { 
        warning: 'bg-amber-600 hover:bg-amber-500 text-white rounded-xl', 
        danger: 'bg-red-600 hover:bg-red-500 text-white rounded-xl', 
        info: 'bg-blue-600 hover:bg-blue-500 text-white rounded-xl', 
        success: 'bg-green-600 hover:bg-green-500 text-white rounded-xl' 
    }; 
    return classes[props.type]; 
}); 

function confirm() { 
    emit('confirm', inputValue.value); 
    inputValue.value = ''; 
} 

function cancel() { 
    emit('cancel'); 
    emit('update:modelValue', false); 
    inputValue.value = ''; 
} 
</script>
