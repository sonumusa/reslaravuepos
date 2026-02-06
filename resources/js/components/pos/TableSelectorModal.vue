<template> 
    <Modal v-model="isOpen" title="Select Table" size="lg"> 
        <!-- Floor Tabs --> 
        <div class="flex gap-2 mb-4 overflow-x-auto no-scrollbar"> 
            <button 
                v-for="floor in floors" 
                :key="floor" 
                @click="selectedFloor = floor" 
                :class="[ 
                    'px-4 py-2 rounded-xl font-medium whitespace-nowrap transition-colors focus:outline-none', 
                    selectedFloor === floor 
                        ? 'bg-blue-600 text-white' 
                        : 'bg-slate-700 hover:bg-slate-600 text-slate-300' 
                ]" 
            > 
                {{ floor }} 
            </button> 
        </div> 
        
        <!-- Tables Grid --> 
        <div class="grid grid-cols-4 sm:grid-cols-5 gap-3 max-h-96 overflow-y-auto"> 
            <button 
                v-for="table in filteredTables" 
                :key="table.id" 
                @click="selectTable(table)" 
                :disabled="table.status === 'occupied'" 
                :class="[ 
                    'rounded-xl p-4 text-center transition-all focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed', 
                    getTableClass(table) 
                ]" 
            > 
                <i class="fas fa-chair text-2xl mb-1"></i> 
                <p class="font-bold">{{ table.name }}</p> 
                <p class="text-xs opacity-80">{{ table.seats }} seats</p> 
                <p class="text-xs mt-1 capitalize">{{ table.status }}</p> 
            </button> 
        </div> 
        
        <!-- Legend --> 
        <div class="flex gap-4 mt-4 pt-4 border-t border-slate-700 text-sm"> 
            <div class="flex items-center gap-2"> 
                <div class="w-4 h-4 bg-green-600 rounded"></div> 
                <span class="text-slate-400">Available</span> 
            </div> 
            <div class="flex items-center gap-2"> 
                <div class="w-4 h-4 bg-red-500/50 rounded"></div> 
                <span class="text-slate-400">Occupied</span> 
            </div> 
            <div class="flex items-center gap-2"> 
                <div class="w-4 h-4 bg-amber-500/50 rounded"></div> 
                <span class="text-slate-400">Reserved</span> 
            </div> 
        </div> 
    </Modal> 
</template> 

<script setup> 
import { ref, computed } from 'vue'; 
import Modal from '@/components/common/Modal.vue'; 

const props = defineProps({ 
    modelValue: Boolean, 
    tables: { 
        type: Array, 
        default: () => [] 
    } 
}); 

const emit = defineEmits(['update:modelValue', 'select']); 

const isOpen = computed({ 
    get: () => props.modelValue, 
    set: (val) => emit('update:modelValue', val) 
}); 

const selectedFloor = ref('Ground'); 

const floors = computed(() => { 
    const floorSet = new Set(props.tables.map(t => t.floor)); 
    return Array.from(floorSet).length ? Array.from(floorSet) : ['Ground']; 
}); 

const filteredTables = computed(() => { 
    return props.tables.filter(t => t.floor === selectedFloor.value); 
}); 

function getTableClass(table) { 
    const classes = { 
        available: 'bg-green-600 hover:bg-green-500 text-white', 
        occupied: 'bg-red-500/50 text-white', 
        reserved: 'bg-amber-500/50 hover:bg-amber-500 text-white', 
        maintenance: 'bg-slate-600 text-slate-300' 
    }; 
    return classes[table.status] || classes.available; 
} 

function selectTable(table) { 
    if (table.status !== 'occupied') { 
        emit('select', table); 
        emit('update:modelValue', false); 
    } 
} 
</script>
