<template> 
    <div class="bg-slate-800/50 px-4 py-2 border-b border-slate-700"> 
        <div class="flex gap-2 overflow-x-auto no-scrollbar scroll-smooth" ref="scrollContainer"> 
            <!-- All Items Tab --> 
            <button 
                @click="selectCategory(null)" 
                :class="[ 
                    'category-tab flex-shrink-0 px-4 py-2 rounded-xl font-medium whitespace-nowrap transition-colors focus:outline-none', 
                    selectedId === null 
                        ? 'bg-orange-500 text-white' 
                        : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                ]" 
            > 
                <i class="fas fa-th-large mr-2"></i> 
                All Items 
            </button> 
            
            <!-- Category Tabs --> 
            <button 
                v-for="category in categories" 
                :key="category.id" 
                @click="selectCategory(category.id)" 
                :class="[ 
                    'category-tab flex-shrink-0 px-4 py-2 rounded-xl font-medium whitespace-nowrap transition-colors focus:outline-none', 
                    selectedId === category.id 
                        ? 'text-white' 
                        : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                ]" 
                :style="selectedId === category.id ? { backgroundColor: category.color || '#3B82F6' } : {}" 
            > 
                <i :class="[category.icon || 'fas fa-utensils', 'mr-2']"></i> 
                {{ category.name }} 
                <span v-if="category.menu_items_count" class="ml-2 bg-black/20 px-1.5 py-0.5 rounded text-xs"> 
                    {{ category.menu_items_count }} 
                </span> 
            </button> 
        </div> 
    </div> 
</template> 

<script setup> 
import { ref } from 'vue'; 

const props = defineProps({ 
    categories: { 
        type: Array, 
        default: () => [] 
    }, 
    selectedId: { 
        type: [Number, null], 
        default: null 
    } 
}); 

const emit = defineEmits(['select']); 

const scrollContainer = ref(null); 

function selectCategory(id) { 
    emit('select', id); 
} 
</script> 

<style scoped> 
.category-tab { 
    scroll-snap-align: start; 
} 
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
