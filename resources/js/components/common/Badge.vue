<template> 
    <span 
        :class="[ 
            'inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full', 
            variantClasses 
        ]" 
    > 
        <i v-if="icon" :class="icon"></i> 
        <slot>{{ text }}</slot> 
    </span> 
</template> 

<script setup> 
import { computed } from 'vue'; 

const props = defineProps({ 
    variant: { 
        type: String, 
        default: 'default', 
        validator: (v) => ['default', 'success', 'warning', 'danger', 'info', 'purple'].includes(v) 
    }, 
    text: String, 
    icon: String 
}); 

const variantClasses = computed(() => { 
    const variants = { 
        default: 'bg-slate-500/20 text-slate-400', 
        success: 'bg-green-500/20 text-green-400', 
        warning: 'bg-amber-500/20 text-amber-400', 
        danger: 'bg-red-500/20 text-red-400', 
        info: 'bg-blue-500/20 text-blue-400', 
        purple: 'bg-purple-500/20 text-purple-400' 
    }; 
    return variants[props.variant]; 
}); 
</script>
