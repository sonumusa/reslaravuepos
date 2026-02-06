<template>
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 border border-slate-700">
        <div :class="['w-2 h-2 rounded-full', statusColor]"></div>
        <span class="text-xs font-medium text-slate-300">{{ statusText }}</span>
        <i v-if="pendingCount > 0" class="fas fa-arrow-up text-xs text-amber-400 ml-1"></i>
        <span v-if="pendingCount > 0" class="text-xs text-amber-400">{{ pendingCount }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSync } from '@/composables/useSync';

const { isSyncing, isOnline, pendingCount } = useSync();

const statusText = computed(() => {
    if (isSyncing.value) return 'Syncing...';
    if (!isOnline.value) return 'Offline';
    if (pendingCount.value > 0) return 'Pending Sync';
    return 'Online';
});

const statusColor = computed(() => {
    if (isSyncing.value) return 'bg-blue-500 animate-pulse';
    if (!isOnline.value) return 'bg-red-500';
    if (pendingCount.value > 0) return 'bg-amber-500';
    return 'bg-green-500';
});
</script>
