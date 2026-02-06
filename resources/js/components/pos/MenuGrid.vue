<template> 
    <div class="flex-1 overflow-y-auto p-4 bg-slate-900"> 
        <!-- Loading State --> 
        <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"> 
            <div 
                v-for="n in 8" 
                :key="n" 
                class="bg-slate-800 rounded-xl p-4 animate-pulse h-40" 
            > 
                <div class="w-full h-20 bg-slate-700 rounded-lg mb-3"></div> 
                <div class="h-4 bg-slate-700 rounded w-3/4 mb-2"></div> 
                <div class="h-5 bg-slate-700 rounded w-1/2"></div> 
            </div> 
        </div> 
        
        <!-- Empty State --> 
        <EmptyState 
            v-else-if="items.length === 0" 
            icon="fas fa-utensils" 
            title="No Menu Items" 
            message="No items found in this category." 
        /> 
        
        <!-- Menu Grid --> 
        <div v-else class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"> 
            <MenuItem 
                v-for="item in items" 
                :key="item.id" 
                :item="item" 
                @click="$emit('select', item)" 
            /> 
        </div> 
    </div> 
</template> 

<script setup> 
import MenuItem from './MenuItem.vue'; 
import EmptyState from '@/components/common/EmptyState.vue'; 

defineProps({ 
    items: { 
        type: Array, 
        default: () => [] 
    }, 
    loading: { 
        type: Boolean, 
        default: false 
    } 
}); 

defineEmits(['select']); 
</script>
