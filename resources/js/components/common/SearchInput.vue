<template> 
    <div class="relative"> 
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i> 
        <input 
            :value="modelValue" 
            @input="$emit('update:modelValue', $event.target.value)" 
            type="text" 
            :placeholder="placeholder" 
            class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-11 pr-10 py-3 text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none" 
            @keyup.enter="$emit('search', modelValue)" 
        > 
        <button 
            v-if="modelValue" 
            @click="clear" 
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1" 
        > 
            <i class="fas fa-times"></i> 
        </button> 
    </div> 
</template> 

<script setup> 
defineProps({ 
    modelValue: { 
        type: String, 
        default: '' 
    }, 
    placeholder: { 
        type: String, 
        default: 'Search...' 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'search', 'clear']); 

function clear() { 
    emit('update:modelValue', ''); 
    emit('clear'); 
} 
</script>
