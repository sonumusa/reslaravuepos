<template>
    <div class="h-screen flex flex-col bg-slate-900">
        <!-- Header -->
        <header class="bg-slate-800 px-6 py-4 flex items-center justify-between border-b border-slate-700 shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-fire text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Kitchen Display</h1>
                    <p class="text-slate-400 text-sm">{{ branchName }}</p>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-amber-400">{{ stats.pending }}</p>
                    <p class="text-xs text-slate-400">Pending</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-400">{{ stats.preparing }}</p>
                    <p class="text-xs text-slate-400">Preparing</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-400">{{ stats.ready }}</p>
                    <p class="text-xs text-slate-400">Ready</p>
                </div>
                <div class="text-center border-l border-slate-700 pl-6">
                    <p class="text-3xl font-bold">{{ stats.avg_prep_time }}<span class="text-lg">m</span></p>
                    <p class="text-xs text-slate-400">Avg Time</p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-4">
                <SyncStatusBadge />
                
                <button 
                    @click="refreshOrders" 
                    :disabled="loading" 
                    class="btn-touch bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl"
                >
                    <i :class="['fas fa-refresh', loading && 'fa-spin']"></i>
                </button>
                
                <button 
                    @click="toggleFullscreen" 
                    class="btn-touch bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl"
                >
                    <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>
                
                <div class="text-right">
                    <p class="text-2xl font-mono font-bold">{{ currentTime }}</p>
                    <p class="text-xs text-slate-400">{{ currentDate }}</p>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-hidden p-4">
            <!-- Empty State -->
            <div v-if="orders.length === 0 && !loading" class="h-full flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-utensils text-6xl text-slate-700 mb-4"></i>
                    <h2 class="text-2xl font-bold text-slate-500">No Orders</h2>
                    <p class="text-slate-600">Waiting for orders from waiters...</p>
                </div>
            </div>
            
            <!-- Orders Grid -->
            <div v-else class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 h-full overflow-y-auto">
                <KdsOrderCard 
                    v-for="order in sortedOrders" 
                    :key="order.id" 
                    :order="order" 
                    @acknowledge="acknowledgeOrder" 
                    @start="startOrder" 
                    @item-ready="markItemReady" 
                    @bump="bumpOrder" 
                    @recall="recallOrder" 
                />
            </div>
        </main>
        
        <!-- Sound Alert (hidden) -->
        <audio ref="alertSound" preload="auto">
            <source src="/sounds/new-order.mp3" type="audio/mpeg">
        </audio>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useOrdersStore } from '@/stores/orders'; // Using ordersStore for local data access if needed
import api from '@/services/api';
import SyncStatusBadge from '@/components/common/SyncStatusBadge.vue';
import KdsOrderCard from '@/components/kds/KdsOrderCard.vue';

const authStore = useAuthStore();
const ordersStore = useOrdersStore();

// State
const loading = ref(false);
const orders = ref([]);
const stats = ref({
    pending: 0,
    preparing: 0,
    ready: 0,
    avg_prep_time: 0
});
const isFullscreen = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const alertSound = ref(null);

// Computed
const branchName = computed(() => authStore.branch?.name || 'Kitchen');

const sortedOrders = computed(() => {
    return [...orders.value].sort((a, b) => {
        // Priority first
        if (a.is_priority && !b.is_priority) return -1;
        if (!a.is_priority && b.is_priority) return 1;
        
        // Then by status (preparing before pending)
        const statusOrder = { preparing: 0, sent_to_kitchen: 1, ready: 2 };
        const statusDiff = (statusOrder[a.status] || 99) - (statusOrder[b.status] || 99);
        if (statusDiff !== 0) return statusDiff;
        
        // Then by time (oldest first)
        return new Date(a.sent_to_kitchen_at || a.created_at) - new Date(b.sent_to_kitchen_at || b.created_at);
    });
});

// Methods
async function fetchOrders() {
    try {
        // Using kdsApi endpoint if available, otherwise fallback to orders?
        // Step 26 api.js defined kdsApi.getOrders().
        // Let's use the direct api call as per prompt, but check api.js exists
        // api.js has kdsApi exported.
        
        // Using direct API call as per prompt code
        const response = await api.get('/kds');
        const newOrders = response.data.data;
        
        // Check for new orders and play sound
        // Simple check: count increased
        if (newOrders.length > orders.value.length) {
            playAlert();
        }
        
        orders.value = newOrders;
    } catch (error) {
        console.error('Failed to fetch KDS orders:', error);
        // Fallback for demo/offline if needed: get from ordersStore
        // orders.value = ordersStore.kitchenOrders;
    }
}

async function fetchStats() {
    try {
        const response = await api.get('/kds/stats');
        stats.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch KDS stats:', error);
    }
}

async function refreshOrders() {
    loading.value = true;
    try {
        await Promise.all([fetchOrders(), fetchStats()]);
    } finally {
        loading.value = false;
    }
}

async function acknowledgeOrder(order) {
    try {
        await api.post(`/kds/${order.id}/acknowledge`);
        await refreshOrders();
    } catch (error) {
        console.error('Failed to acknowledge order:', error);
    }
}

async function startOrder(order) {
    try {
        await api.post(`/kds/${order.id}/start`);
        await refreshOrders();
    } catch (error) {
        console.error('Failed to start order:', error);
    }
}

async function markItemReady(order, item) {
    try {
        await api.post(`/kds/${order.id}/item/${item.id}/ready`);
        
        // Optimistic update
        const orderIdx = orders.value.findIndex(o => o.id === order.id);
        if (orderIdx >= 0) {
            const itemIdx = orders.value[orderIdx].items.findIndex(i => i.id === item.id);
            if (itemIdx >= 0) {
                orders.value[orderIdx].items[itemIdx].status = 'ready';
            }
        }
        
        // Then refresh
        // await refreshOrders(); 
    } catch (error) {
        console.error('Failed to mark item ready:', error);
    }
}

async function bumpOrder(order) {
    try {
        await api.post(`/kds/${order.id}/bump`);
        await refreshOrders();
    } catch (error) {
        console.error('Failed to bump order:', error);
    }
}

async function recallOrder(order) {
    try {
        await api.post(`/kds/${order.id}/recall`);
        await refreshOrders();
    } catch (error) {
        console.error('Failed to recall order:', error);
    }
}

function playAlert() {
    if (alertSound.value) {
        alertSound.value.play().catch(() => {});
    }
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        document.exitFullscreen();
        isFullscreen.value = false;
    }
}

function updateClock() {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short'
    });
}

// Lifecycle
let refreshInterval;
let clockInterval;

onMounted(async () => {
    // Initial load
    loading.value = true;
    await refreshOrders();
    loading.value = false;
    
    // Start clock
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
    
    // Auto-refresh every 10 seconds
    refreshInterval = setInterval(refreshOrders, 10000);
    
    // Listen for fullscreen changes
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });
});

onUnmounted(() => {
    clearInterval(refreshInterval);
    clearInterval(clockInterval);
});
</script>
