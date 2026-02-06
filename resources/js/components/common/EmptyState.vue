<template> 
    <div class="flex flex-col items-center justify-center py-12 text-center"> 
        <div 
            :class="[ 
                'w-20 h-20 rounded-full flex items-center justify-center mb-4', 
                iconBgClass 
            ]" 
        > 
            <i :class="[icon, 'text-3xl', iconColorClass]"></i> 
        </div> 
        <h3 class="text-lg font-semibold mb-2 text-white">{{ title }}</h3> 
        <p class="text-slate-400 text-sm max-w-sm mb-6">{{ message }}</p> 
        <slot name="action"> 
            <button 
                v-if="actionText" 
                @click="$emit('action')" 
                class="bg-blue-600 hover:bg-blue-500 text-white rounded-xl px-6 py-2 transition-colors font-medium" 
            > 
                <i v-if="actionIcon" :class="[actionIcon, 'mr-2']"></i> 
                {{ actionText }} 
            </button> 
        </slot> 
    </div> 
</template> 

<script setup> 
import { computed } from 'vue'; 

const props = defineProps({ 
    icon: { 
        type: String, 
        default: 'fas fa-inbox' 
    }, 
    title: { 
        type: String, 
        default: 'No Data' 
    }, 
    message: { 
        type: String, 
        default: 'There is nothing to display here yet.' 
    }, 
    variant: { 
        type: String, 
        default: 'default' 
    }, 
    actionText: String, 
    actionIcon: String 
}); 

defineEmits(['action']); 

const iconBgClass = computed(() => { 
    const bgs = { 
        default: 'bg-slate-700', 
        warning: 'bg-amber-500/20', 
        info: 'bg-blue-500/20' 
    }; 
    return bgs[props.variant] || bgs.default; 
}); 

const iconColorClass = computed(() => { 
    const colors = { 
        default: 'text-slate-400', 
        warning: 'text-amber-400', 
        info: 'text-blue-400' 
    }; 
    return colors[props.variant] || colors.default; 
}); 
</script>
