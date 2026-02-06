<template> 
    <button 
        @click="$emit('click', item)" 
        :class="[ 
            'menu-item-card text-left relative overflow-hidden bg-slate-800 rounded-xl p-3 hover:bg-slate-700 transition-colors w-full', 
            !item.is_available && 'opacity-50 cursor-not-allowed' 
        ]" 
        :disabled="!item.is_available" 
    > 
        <!-- Special Badge --> 
        <div 
            v-if="item.special_price" 
            class="absolute top-2 right-2 bg-pink-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 shadow-sm" 
        > 
            SPECIAL 
        </div> 
        
        <!-- Unavailable Badge --> 
        <div 
            v-if="!item.is_available" 
            class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 shadow-sm" 
        > 
            SOLD OUT 
        </div> 
        
        <!-- Image or Icon --> 
        <div class="mb-3"> 
            <img 
                v-if="item.image" 
                :src="item.image" 
                :alt="item.name" 
                class="w-full h-20 object-cover rounded-lg" 
            > 
            <div v-else class="w-full h-20 bg-slate-700 rounded-lg flex items-center justify-center"> 
                <span class="text-3xl">{{ getEmoji(item) }}</span> 
            </div> 
        </div> 
        
        <!-- Name --> 
        <p class="font-medium text-sm mb-1 line-clamp-2 text-white h-10">{{ item.name }}</p> 
        
        <!-- Price --> 
        <div class="flex items-center gap-2 mt-auto"> 
            <span v-if="item.special_price" class="text-xs text-slate-400 line-through"> 
                Rs. {{ item.price.toLocaleString() }} 
            </span> 
            <span class="text-green-400 font-bold text-sm"> 
                Rs. {{ (item.special_price || item.price).toLocaleString() }} 
            </span> 
        </div> 
        
        <!-- Tags --> 
        <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-1 mt-2"> 
            <span 
                v-for="tag in item.tags.slice(0, 2)" 
                :key="tag" 
                class="text-[10px] bg-slate-700 text-slate-400 px-1.5 py-0.5 rounded" 
            > 
                {{ tag }} 
            </span> 
        </div> 
    </button> 
</template> 

<script setup> 
const props = defineProps({ 
    item: { 
        type: Object, 
        required: true 
    } 
}); 

defineEmits(['click']); 

function getEmoji(item) { 
    const categoryEmojis = { 
        'bbq': '🍖', 
        'karahi': '🍲', 
        'rice': '🍚', 
        'starters': '🥟', 
        'beverages': '🥤', 
        'desserts': '🍨', 
        'fast-food': '🍔',
        'breakfast': '🍳'
    }; 
    return categoryEmojis[item.category?.slug] || '🍽️'; 
} 
</script>
