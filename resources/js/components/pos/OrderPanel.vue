<template>
    <div class="w-96 bg-slate-800 flex flex-col border-l border-slate-700 h-full">
        <!-- Order Header -->
        <div class="p-4 border-b border-slate-700">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-bold text-lg text-white">Current Order</h2>
                <Badge :variant="statusVariant">{{ orderStatus }}</Badge>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-400">
                <span class="flex items-center">
                    <i class="fas fa-hashtag mr-1"></i>{{ orderNumber }}
                </span>
                <span class="flex items-center">
                    <i class="fas fa-chair mr-1"></i>{{ tableName }}
                </span>
                <span class="flex items-center">
                    <i class="fas fa-user mr-1"></i>{{ customerName }}
                </span>
            </div>
        </div>

        <!-- Order Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-slate-900/30">
            <EmptyState
                v-if="!hasItems"
                icon="fas fa-shopping-basket"
                title="No items in order"
                message="Tap menu items to add"
            />

            <template v-else>
                <OrderItem
                    v-for="(item, index) in orderItems"
                    :key="item.id || index"
                    :item="item"
                    :editable="editable"
                    @increment="$emit('increment', item)"
                    @decrement="$emit('decrement', item)"
                    @remove="$emit('remove', item)"
                    @edit="$emit('edit-item', item)"
                />
            </template>
        </div>

        <!-- Order Notes Button -->
        <div class="px-4 pt-2">
            <button
                @click="$emit('add-notes')"
                class="w-full flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-600 rounded-xl py-2 text-sm text-slate-300 transition-colors"
            >
                <i class="fas fa-sticky-note"></i>
                <span>{{ hasNotes ? 'Edit Notes' : 'Add Order Notes' }}</span>
            </button>
        </div>

        <!-- Discount Button -->
        <div class="px-4 py-2">
            <button
                @click="$emit('apply-discount')"
                :class="[
                    'w-full flex items-center justify-center gap-2 rounded-xl py-2 text-sm transition-colors',
                    hasDiscount
                        ? 'bg-purple-600 text-white'
                        : 'bg-purple-500/20 text-purple-400 hover:bg-purple-500/30'
                ]"
            >
                <i class="fas fa-percent"></i>
                <span v-if="hasDiscount">
                    Discount: {{ discountDisplay }}
                </span>
                <span v-else>Apply Discount</span>
            </button>
        </div>

        <!-- Order Totals -->
        <div class="p-4 bg-slate-700/30 space-y-2 text-sm border-t border-slate-700">
            <div class="flex justify-between">
                <span class="text-slate-400">Subtotal</span>
                <span class="text-white">Rs. {{ subtotal.toLocaleString() }}</span>
            </div>
            <div v-if="discountAmount > 0" class="flex justify-between text-green-400">
                <span>Discount</span>
                <span>- Rs. {{ discountAmount.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">GST ({{ taxRate }}%)</span>
                <span class="text-white">Rs. {{ taxAmount.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-xl font-bold pt-3 border-t border-slate-600 mt-2">
                <span class="text-white">Total</span>
                <span class="text-green-400">Rs. {{ total.toLocaleString() }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-4 space-y-2 border-t border-slate-700 bg-slate-800">
            <slot name="actions"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import OrderItem from './OrderItem.vue';
import Badge from '@/components/common/Badge.vue';
import EmptyState from '@/components/common/EmptyState.vue';

const props = defineProps({
    order: {
        type: Object,
        default: null
    },
    editable: {
        type: Boolean,
        default: true
    },
    taxRate: {
        type: Number,
        default: 16
    }
});

defineEmits(['increment', 'decrement', 'remove', 'edit-item', 'add-notes', 'apply-discount']);

// ═══════════════════════════════════════════════════════
// SAFE COMPUTED PROPERTIES (Handle null order)
// ═══════════════════════════════════════════════════════

const orderStatus = computed(() => props.order?.status || 'OPEN');
const orderNumber = computed(() => props.order?.order_number || 'NEW');
const tableName = computed(() => props.order?.table?.name || props.order?.table_name || 'No Table');
const customerName = computed(() => props.order?.customer?.name || 'Walk-in');
const orderItems = computed(() => props.order?.items || []);
const hasItems = computed(() => orderItems.value.length > 0);
const hasNotes = computed(() => !!props.order?.notes);
const hasDiscount = computed(() => !!props.order?.discount?.value);

const discountDisplay = computed(() => {
    const discount = props.order?.discount;
    if (!discount?.value) return '';
    return discount.type === 'percentage' 
        ? discount.value + '%' 
        : 'Rs. ' + discount.value;
});

const subtotal = computed(() => {
    if (!props.order?.items) return 0;
    return props.order.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
});

const discountAmount = computed(() => {
    const discount = props.order?.discount;
    if (!discount?.value) return 0;

    if (discount.type === 'percentage') {
        return Math.round(subtotal.value * (discount.value / 100));
    }
    return Number(discount.value);
});

const afterDiscount = computed(() => subtotal.value - discountAmount.value);

const taxAmount = computed(() => Math.round(afterDiscount.value * (props.taxRate / 100)));

const total = computed(() => afterDiscount.value + taxAmount.value);

const statusVariant = computed(() => {
    const variants = {
        'draft': 'default',
        'open': 'info',
        'hold': 'warning',
        'completed': 'success',
        'paid': 'success',
        'cancelled': 'danger',
        'void': 'danger'
    };
    return variants[props.order?.status] || 'default';
});
</script>