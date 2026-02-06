<template> 
    <div class="order-item-row group flex items-center gap-3 p-3 bg-slate-800 rounded-xl hover:bg-slate-750 transition-colors border border-slate-700/50"> 
        <!-- Item Icon/Image --> 
        <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0 text-white"> 
            <span class="text-lg">{{ getEmoji() }}</span> 
        </div> 
        
        <!-- Item Details --> 
        <div class="flex-1 min-w-0"> 
            <p class="font-medium text-sm truncate text-white">{{ item.item_name }}</p> 
            <p class="text-xs text-slate-400">Rs. {{ item.unit_price.toLocaleString() }} each</p> 
            
            <!-- Modifiers --> 
            <div v-if="item.modifiers?.length" class="flex flex-wrap gap-1 mt-1"> 
                <span 
                    v-for="mod in item.modifiers" 
                    :key="mod.id" 
                    class="text-[10px] bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded" 
                > 
                    {{ mod.modifier_name }} 
                </span> 
            </div> 
            
            <!-- Notes --> 
            <p v-if="item.notes" class="text-xs text-amber-400 mt-1 truncate flex items-center"> 
                <i class="fas fa-sticky-note mr-1 text-[10px]"></i>{{ item.notes }} 
            </p> 
        </div> 
        
        <!-- Quantity Controls --> 
        <div v-if="editable" class="flex items-center gap-1 bg-slate-700 rounded-lg p-0.5"> 
            <button 
                @click="$emit('decrement', item)" 
                class="w-7 h-7 hover:bg-red-500 hover:text-white rounded flex items-center justify-center transition-colors text-slate-400" 
            > 
                <i class="fas fa-minus text-xs"></i> 
            </button> 
            <span class="w-6 text-center font-bold text-sm text-white">{{ item.quantity }}</span> 
            <button 
                @click="$emit('increment', item)" 
                class="w-7 h-7 hover:bg-green-500 hover:text-white rounded flex items-center justify-center transition-colors text-slate-400" 
            > 
                <i class="fas fa-plus text-xs"></i> 
            </button> 
        </div> 
        
        <!-- Quantity Display (non-editable) --> 
        <span v-else class="w-8 text-center font-bold text-white">x{{ item.quantity }}</span> 
        
        <!-- Subtotal --> 
        <p class="font-bold text-green-400 w-16 text-right text-sm"> 
            {{ item.subtotal.toLocaleString() }} 
        </p> 
        
        <!-- Remove Button (on hover) --> 
        <button 
            v-if="editable" 
            @click="$emit('remove', item)" 
            class="w-8 h-8 bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white rounded-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 focus:opacity-100" 
        > 
            <i class="fas fa-trash text-xs"></i> 
        </button> 
    </div> 
</template> 

<script setup> 
const props = defineProps({ 
    item: { 
        type: Object, 
        required: true 
    }, 
    editable: { 
        type: Boolean, 
        default: true 
    } 
}); 

defineEmits(['increment', 'decrement', 'remove', 'edit']); 

function getEmoji() { 
    return props.item.menuItem?.category?.icon || '🍽️'; 
} 
</script>
