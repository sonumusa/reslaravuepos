<template>
    <div class="h-full flex flex-col bg-slate-900 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Select Table</h1>
                <p class="text-slate-400">Choose a table to start an order</p>
            </div>
            <button 
                @click="refreshTables" 
                :disabled="loading" 
                class="btn-secondary px-4 py-2"
            >
                <i :class="['fas fa-refresh mr-2', loading && 'fa-spin']"></i>
                Refresh
            </button>
        </div>
        
        <!-- Floor Tabs -->
        <div class="flex gap-2 mb-6">
            <button 
                v-for="floor in floors" 
                :key="floor" 
                @click="selectedFloor = floor"
                :class="[
                    'btn-touch px-6 py-3 rounded-xl font-medium transition-colors',
                    selectedFloor === floor 
                        ? 'bg-blue-600 text-white' 
                        : 'bg-slate-700 hover:bg-slate-600'
                ]"
            >
                {{ floor }}
            </button>
        </div>
        
        <!-- Tables Grid -->
        <div class="flex-1 overflow-y-auto">
            <div class="grid grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4">
                <button 
                    v-for="table in filteredTables" 
                    :key="table.id" 
                    @click="selectTable(table)"
                    :disabled="table.status === 'occupied' || table.status === 'maintenance'"
                    :class="[
                        'aspect-square rounded-2xl p-4 flex flex-col items-center justify-center transition-all',
                        getTableClass(table)
                    ]"
                >
                    <i class="fas fa-chair text-4xl mb-2"></i>
                    <p class="font-bold text-lg">{{ table.name }}</p>
                    <p class="text-sm opacity-80">{{ table.seats }} seats</p>
                    <div class="mt-2">
                        <span :class="['text-xs font-semibold px-2 py-1 rounded-full', getStatusBadgeClass(table)]">
                            {{ table.status }}
                        </span>
                    </div>
                    
                    <!-- Active Order Info -->
                    <div v-if="table.active_order" class="mt-2 text-xs text-center">
                        <p class="text-amber-400">{{ table.active_order.order_number }}</p>
                        <p class="text-slate-400">{{ table.active_order.items_count }} items</p>
                    </div>
                </button>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="flex justify-center gap-6 mt-6 pt-4 border-t border-slate-700">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-600 rounded"></div>
                <span class="text-slate-400 text-sm">Available</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-500 rounded"></div>
                <span class="text-slate-400 text-sm">Occupied</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-amber-500 rounded"></div>
                <span class="text-slate-400 text-sm">Reserved</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-slate-600 rounded"></div>
                <span class="text-slate-400 text-sm">Maintenance</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { usePosStore } from '@/stores/pos';
// import { useOrdersStore } from '@/stores/orders'; 

const router = useRouter();
const posStore = usePosStore();
// const ordersStore = useOrdersStore(); 

const loading = ref(false);
const selectedFloor = ref('Ground');

const tables = computed(() => posStore.tables);
const floors = computed(() => {
    const floorSet = new Set(tables.value.map(t => t.floor).filter(Boolean));
    const list = Array.from(floorSet);
    return list.length ? list : ['Ground'];
});

const filteredTables = computed(() => {
    return tables.value.filter(t => (t.floor || 'Ground') === selectedFloor.value);
});

function getTableClass(table) {
    const classes = {
        available: 'bg-green-600 hover:bg-green-500 text-white cursor-pointer',
        occupied: 'bg-red-500/30 text-red-300 cursor-not-allowed',
        reserved: 'bg-amber-500 hover:bg-amber-400 text-white cursor-pointer',
        maintenance: 'bg-slate-600/50 text-slate-400 cursor-not-allowed'
    };
    return classes[table.status] || classes.available;
}

function getStatusBadgeClass(table) {
    const classes = {
        available: 'bg-green-900/50 text-green-300',
        occupied: 'bg-red-900/50 text-red-300',
        reserved: 'bg-amber-900/50 text-amber-300',
        maintenance: 'bg-slate-700 text-slate-400'
    };
    return classes[table.status] || '';
}

function selectTable(table) {
    if (table.status === 'occupied' || table.status === 'maintenance') {
        return;
    }
    
    // Changed from ordersStore.setTable(table) to posStore.selectTable(table)
    // based on store analysis.
    posStore.selectTable(table);
    
    // window.$toast?.success(`${table.name} selected`);
    router.push({ name: 'waiter-pos' });
}

async function refreshTables() {
    loading.value = true;
    try {
        await posStore.fetchTables();
        // window.$toast?.success('Tables refreshed');
    } catch (error) {
        // window.$toast?.error('Failed to refresh tables');
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    loading.value = true;
    try {
        await posStore.fetchTables();
    } finally {
        loading.value = false;
    }
    
    // Set default floor
    if (floors.value.length > 0 && !floors.value.includes(selectedFloor.value)) {
        selectedFloor.value = floors.value[0];
    }
});
</script>
