<template>
    <div 
        :class="[
            'bg-slate-800 rounded-2xl overflow-hidden flex flex-col transition-all',
            order.is_priority && 'ring-2 ring-red-500',
            isDelayed && 'ring-2 ring-amber-500 animate-pulse'
        ]"
    >
        <!-- Header -->
        <div 
            :class="[
                'px-4 py-3 flex items-center justify-between',
                headerBgClass
            ]"
        >
            <div class="flex items-center gap-3">
                <span class="font-bold text-lg">{{ order.table_name || 'Table ?' }}</span>
                <Badge v-if="order.is_priority" variant="danger">
                    <i class="fas fa-bolt mr-1"></i>RUSH
                </Badge>
            </div>
            
            <!-- Timer -->
            <div 
                :class="[
                    'font-mono font-bold text-xl',
                    timerColorClass
                ]"
            >
                {{ formattedTime }}
            </div>
        </div>
        
        <!-- Order Number & Waiter -->
        <div class="px-4 py-2 bg-slate-700/50 flex items-center justify-between text-sm">
            <span class="font-mono">{{ order.order_number }}</span>
            <span class="text-slate-400">{{ order.waiter_name || 'Unknown' }}</span>
        </div>
        
        <!-- Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div 
                v-for="item in order.items" 
                :key="item.id" 
                @click="$emit('item-ready', order, item)"
                :class="[
                    'p-3 rounded-xl cursor-pointer transition-all',
                    getItemClass(item)
                ]"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span 
                            :class="[
                                'w-8 h-8 rounded-lg flex items-center justify-center font-bold text-lg',
                                item.status === 'ready' ? 'bg-green-600' : 'bg-slate-600'
                            ]"
                        >
                            {{ item.quantity }}
                        </span>
                        <div>
                            <p class="font-medium">{{ item.item_name }}</p>
                            <!-- Modifiers -->
                            <div v-if="item.modifiers?.length" class="flex flex-wrap gap-1 mt-1">
                                <span 
                                    v-for="mod in item.modifiers" 
                                    :key="mod.id"
                                    class="text-xs bg-blue-500/30 text-blue-300 px-1.5 py-0.5 rounded"
                                >
                                    {{ mod.modifier_name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <i 
                        v-if="item.status === 'ready'" 
                        class="fas fa-check-circle text-green-400 text-xl"
                    ></i>
                </div>
                
                <!-- Notes -->
                <p v-if="item.notes" class="text-xs text-amber-400 mt-2 pl-10">
                    <i class="fas fa-sticky-note mr-1"></i>{{ item.notes }}
                </p>
            </div>
        </div>
        
        <!-- Kitchen Notes -->
        <div v-if="order.kitchen_notes" class="px-4 py-2 bg-amber-500/20 text-amber-300 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ order.kitchen_notes }}
        </div>
        
        <!-- Action Buttons -->
        <div class="p-4 border-t border-slate-700">
            <!-- Pending State - Acknowledge Button -->
            <button 
                v-if="order.status === 'sent_to_kitchen'" 
                @click="$emit('acknowledge', order)" 
                class="w-full btn-touch bg-amber-500 hover:bg-amber-400 text-slate-900 rounded-xl py-4 font-bold text-lg"
            >
                <i class="fas fa-hand mr-2"></i>ACKNOWLEDGE
            </button>
            
            <!-- Preparing State - Bump Button -->
            <button 
                v-else-if="order.status === 'preparing'" 
                @click="$emit('bump', order)" 
                :disabled="!allItemsReady"
                :class="[
                    'w-full btn-touch rounded-xl py-4 font-bold text-lg',
                    allItemsReady 
                        ? 'bg-green-600 hover:bg-green-500 text-white' 
                        : 'bg-slate-600 text-slate-400 cursor-not-allowed'
                ]"
            >
                <i class="fas fa-check-double mr-2"></i>BUMP
            </button>
            
            <!-- Ready State - Recall Button -->
            <button 
                v-else-if="order.status === 'ready'" 
                @click="$emit('recall', order)" 
                class="w-full btn-touch bg-blue-500 hover:bg-blue-400 text-white rounded-xl py-4 font-bold text-lg"
            >
                <i class="fas fa-undo mr-2"></i>RECALL
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Badge from '@/components/common/Badge.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
});

defineEmits(['acknowledge', 'start', 'item-ready', 'bump', 'recall']);

// Timer
const elapsedSeconds = ref(0);

const formattedTime = computed(() => {
    const mins = Math.floor(elapsedSeconds.value / 60);
    const secs = elapsedSeconds.value % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
});

const isDelayed = computed(() => elapsedSeconds.value > 900); // > 15 minutes

const timerColorClass = computed(() => {
    if (elapsedSeconds.value > 900) return 'text-red-400'; // > 15 min
    if (elapsedSeconds.value > 600) return 'text-amber-400'; // > 10 min
    return 'text-green-400'; // Changed from 'text-white' to match design in prompt if needed, or prompt said 'text-green-400' for Ready? No, timer color logic.
});

const headerBgClass = computed(() => {
    if (props.order.status === 'ready') return 'bg-green-600';
    if (props.order.status === 'preparing') return 'bg-blue-600';
    return 'bg-amber-500'; // sent_to_kitchen / pending
});

const allItemsReady = computed(() => {
    // If no items, consider ready? Or not bumpable. 
    if (!props.order.items || props.order.items.length === 0) return true;
    return props.order.items.every(item => item.status === 'ready');
});

function getItemClass(item) {
    if (item.status === 'ready') {
        return 'bg-green-500/20 border border-green-500/30';
    }
    if (item.status === 'preparing') {
        return 'bg-blue-500/20 border border-blue-500/30';
    }
    return 'bg-slate-700 hover:bg-slate-600';
}

// Update timer
let timerInterval;

function updateTimer() {
    const sentAt = new Date(props.order.sent_to_kitchen_at || props.order.created_at);
    elapsedSeconds.value = Math.floor((Date.now() - sentAt) / 1000);
}

onMounted(() => {
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
    clearInterval(timerInterval);
});
</script>
