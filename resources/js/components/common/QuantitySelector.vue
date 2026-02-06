<template> 
    <div class="flex items-center gap-2"> 
        <button 
            @click="decrement" 
            :disabled="modelValue <= min" 
            :class="[ 
                'w-10 h-10 rounded-lg flex items-center justify-center transition-colors', 
                modelValue <= min 
                    ? 'bg-slate-700 text-slate-500 cursor-not-allowed' 
                    : 'bg-slate-600 hover:bg-red-500 text-white' 
            ]" 
        > 
            <i class="fas fa-minus text-sm"></i> 
        </button> 
        
        <span class="w-10 text-center font-bold text-lg text-white">{{ modelValue }}</span> 
        
        <button 
            @click="increment" 
            :disabled="modelValue >= max" 
            :class="[ 
                'w-10 h-10 rounded-lg flex items-center justify-center transition-colors', 
                modelValue >= max 
                    ? 'bg-slate-700 text-slate-500 cursor-not-allowed' 
                    : 'bg-slate-600 hover:bg-green-500 text-white' 
            ]" 
        > 
            <i class="fas fa-plus text-sm"></i> 
        </button> 
    </div> 
</template> 

<script setup> 
const props = defineProps({ 
    modelValue: { 
        type: Number, 
        default: 1 
    }, 
    min: { 
        type: Number, 
        default: 1 
    }, 
    max: { 
        type: Number, 
        default: 99 
    }, 
    step: { 
        type: Number, 
        default: 1 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'change']); 

function increment() { 
    if (props.modelValue < props.max) { 
        const newValue = props.modelValue + props.step; 
        emit('update:modelValue', newValue); 
        emit('change', newValue); 
    } 
} 

function decrement() { 
    if (props.modelValue > props.min) { 
        const newValue = props.modelValue - props.step; 
        emit('update:modelValue', newValue); 
        emit('change', newValue); 
    } 
} 
</script>
