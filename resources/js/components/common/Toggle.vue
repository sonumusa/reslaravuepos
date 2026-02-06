<template> 
    <button 
        @click="toggle" 
        :class="[ 
            'relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none', 
            modelValue ? 'bg-blue-600' : 'bg-slate-600',
            disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
        ]" 
        :disabled="disabled" 
    > 
        <span 
            :class="[ 
                'inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform', 
                modelValue ? 'translate-x-6' : 'translate-x-1' 
            ]" 
        /> 
    </button> 
</template> 

<script setup> 
const props = defineProps({ 
    modelValue: Boolean, 
    disabled: Boolean 
}); 

const emit = defineEmits(['update:modelValue', 'change']); 

function toggle() { 
    if (props.disabled) return;
    const newValue = !props.modelValue; 
    emit('update:modelValue', newValue); 
    emit('change', newValue); 
} 
</script>
