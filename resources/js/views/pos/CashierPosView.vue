<template>
    <div class="h-full flex">
        <!-- Left Panel - Completed Orders List -->
        <div class="flex-1 flex flex-col bg-slate-900">
            <!-- Header -->
            <div class="bg-slate-800 px-4 py-3 flex items-center gap-4 border-b border-slate-700">
                <h2 class="font-bold text-lg">
                    <i class="fas fa-receipt mr-2 text-green-400"></i>
                    Completed Orders
                </h2>
                
                <div class="flex-1"></div>
                
                <!-- Session Info -->
                <div v-if="currentSession" class="flex items-center gap-2 text-sm">
                    <span class="text-slate-400">Session:</span>
                    <span class="text-green-400 font-medium">{{ formatTime(currentSession.opened_at) }}</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-400">Opening:</span>
                    <span class="font-medium">Rs. {{ currentSession.opening_cash?.toLocaleString() }}</span>
                </div>
                
                <!-- Refresh Button -->
                <button 
                    @click="refreshOrders" 
                    :disabled="loading" 
                    class="btn-touch bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl"
                >
                    <i :class="['fas fa-refresh', loading && 'fa-spin']"></i>
                </button>
                
                <!-- Session Actions -->
                <div class="flex gap-2">
                    <button 
                        v-if="!currentSession" 
                        @click="showOpenSession = true" 
                        class="btn-touch bg-green-600 hover:bg-green-500 px-4 py-2 rounded-xl font-medium"
                    >
                        <i class="fas fa-play mr-2"></i>Open Session
                    </button>
                    <button 
                        v-else 
                        @click="showCloseSession = true" 
                        class="btn-touch bg-red-500/20 text-red-400 hover:bg-red-500/30 px-4 py-2 rounded-xl font-medium"
                    >
                        <i class="fas fa-stop mr-2"></i>Close Session
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="px-4 py-3 flex items-center gap-4 border-b border-slate-700">
                <SearchInput 
                    v-model="searchQuery" 
                    placeholder="Search by order number or table..." 
                    class="w-64" 
                />
                
                <div class="flex gap-2">
                    <button 
                        v-for="filter in orderFilters" 
                        :key="filter.value" 
                        @click="selectedFilter = filter.value" 
                        :class="[
                            'btn-touch px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                            selectedFilter === filter.value 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-slate-700 hover:bg-slate-600'
                        ]"
                    >
                        {{ filter.label }}
                        <span v-if="filter.count" class="ml-2 bg-black/20 px-1.5 py-0.5 rounded text-xs">
                            {{ filter.count }}
                        </span>
                    </button>
                </div>
            </div>
            
            <!-- Orders Grid -->
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="n in 6" :key="n" class="bg-slate-800 rounded-xl p-4 animate-pulse">
                        <div class="h-4 bg-slate-700 rounded w-1/2 mb-3"></div>
                        <div class="h-8 bg-slate-700 rounded mb-3"></div>
                        <div class="h-6 bg-slate-700 rounded w-3/4"></div>
                    </div>
                </div>
                
                <EmptyState 
                    v-else-if="filteredOrders.length === 0" 
                    icon="fas fa-clipboard-list" 
                    title="No Orders" 
                    message="Completed orders from waiters will appear here for payment processing." 
                />
                
                <div v-else class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="order in filteredOrders" 
                        :key="order.id" 
                        @click="selectOrder(order)" 
                        :class="[
                            'bg-slate-800 hover:bg-slate-700 rounded-xl p-4 cursor-pointer transition-all border-2',
                            selectedOrder?.id === order.id 
                                ? 'border-green-500' 
                                : 'border-transparent hover:border-slate-600'
                        ]"
                    >
                        <!-- Order Header -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-mono text-sm">{{ order.order_number }}</span>
                            <Badge :variant="getOrderStatusVariant(order.status)">
                                {{ order.status }}
                            </Badge>
                        </div>
                        
                        <!-- Table & Customer -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chair text-blue-400"></i>
                            </div>
                            <div>
                                <p class="font-bold">{{ order.table_name || 'No Table' }}</p>
                                <p class="text-sm text-slate-400">{{ order.customer_name || 'Walk-in' }}</p>
                            </div>
                        </div>
                        
                        <!-- Items & Waiter -->
                        <div class="flex items-center justify-between text-sm text-slate-400 mb-3">
                            <span>{{ order.items_count || order.items?.length || 0 }} items</span>
                            <span>{{ order.waiter_name || 'Unknown' }}</span>
                        </div>
                        
                        <!-- Total -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-700">
                            <span class="text-slate-400">Total:</span>
                            <span class="text-xl font-bold text-green-400">
                                Rs. {{ calculateOrderTotal(order).toLocaleString() }}
                            </span>
                        </div>
                        
                        <!-- Time -->
                        <p class="text-xs text-slate-500 mt-2">
                            {{ formatTimeAgo(order.completed_at || order.created_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Payment -->
        <div class="w-96 bg-slate-800 flex flex-col border-l border-slate-700">
            <!-- No Order Selected -->
            <div v-if="!selectedOrder" class="flex-1 flex items-center justify-center text-slate-500">
                <div class="text-center">
                    <i class="fas fa-hand-pointer text-5xl mb-4 opacity-50"></i>
                    <p>Select an order to process payment</p>
                </div>
            </div>
            
            <!-- Order Details -->
            <template v-else>
                <!-- Header -->
                <div class="p-4 border-b border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="font-bold text-lg">Payment</h2>
                        <Badge :variant="getOrderStatusVariant(selectedOrder.status)">
                            {{ selectedOrder.status }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-slate-400">
                        <span><i class="fas fa-hashtag mr-1"></i>{{ selectedOrder.order_number }}</span>
                        <span><i class="fas fa-chair mr-1"></i>{{ selectedOrder.table_name }}</span>
                    </div>
                </div>
                
                <!-- Order Items (scrollable) -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    <div 
                        v-for="item in selectedOrder.items" 
                        :key="item.id" 
                        class="flex items-center gap-3 p-2 bg-slate-700/50 rounded-lg"
                    >
                        <!-- <span class="text-xl">{{ getItemEmoji(item) }}</span> -->
                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ item.item_name }}</p>
                            <p class="text-xs text-slate-400">x{{ item.quantity }}</p>
                        </div>
                        <p class="font-bold text-green-400">
                            Rs. {{ item.subtotal?.toLocaleString() }}
                        </p>
                    </div>
                </div>
                
                <!-- Totals -->
                <div class="p-4 bg-slate-700/50 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Subtotal</span>
                        <span>Rs. {{ orderSubtotal.toLocaleString() }}</span>
                    </div>
                    <div v-if="selectedOrder.discount" class="flex justify-between text-green-400">
                        <span>Discount</span>
                        <span>- Rs. {{ orderDiscount.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">GST ({{ taxRate }}%)</span>
                        <span>Rs. {{ orderTax.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-slate-600">
                        <span>Total</span>
                        <span class="text-green-400">Rs. {{ orderTotal.toLocaleString() }}</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="p-4 space-y-2">
                    <button 
                        @click="showPaymentModal = true" 
                        :disabled="selectedOrder.status === 'paid'" 
                        class="w-full btn-touch bg-green-600 hover:bg-green-500 rounded-xl py-4 font-bold text-lg disabled:opacity-50"
                    >
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        Process Payment
                    </button>
                    <button 
                        @click="showSplitBillModal = true" 
                        :disabled="selectedOrder.status === 'paid'" 
                        class="w-full btn-touch bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded-xl py-3 font-medium disabled:opacity-50"
                    >
                        <i class="fas fa-divide mr-2"></i>
                        Split Bill
                    </button>
                    <button 
                        v-if="selectedOrder.status === 'paid'" 
                        @click="reprintReceipt" 
                        class="w-full btn-touch bg-slate-700 hover:bg-slate-600 rounded-xl py-3 font-medium"
                    >
                        <i class="fas fa-print mr-2"></i>
                        Reprint Receipt
                    </button>
                </div>
            </template>
        </div>
        
        <!-- Payment Modal -->
        <PaymentModal 
            v-model="showPaymentModal" 
            :total-amount="orderTotal" 
            :order-items="selectedOrder?.items || []" 
            @process="processPayment" 
        />
        
        <!-- Split Bill Modal -->
        <Modal v-model="showSplitBillModal" title="Split Bill" size="md">
            <div class="space-y-4">
                <p class="text-slate-400 text-center">
                    Total: <span class="text-white font-bold">Rs. {{ orderTotal.toLocaleString() }}</span>
                </p>
                
                <div class="grid grid-cols-4 gap-3">
                    <button 
                        v-for="n in [2, 3, 4, 5]" 
                        :key="n" 
                        @click="splitWays = n" 
                        :class="[
                            'btn-touch p-4 rounded-xl text-center transition-colors',
                            splitWays === n ? 'bg-blue-600' : 'bg-slate-700 hover:bg-slate-600'
                        ]"
                    >
                        <p class="text-2xl font-bold">{{ n }}</p>
                        <p class="text-xs">ways</p>
                    </button>
                </div>
                
                <div class="bg-slate-700 rounded-xl p-4 text-center">
                    <p class="text-sm text-slate-400 mb-2">Each person pays:</p>
                    <p class="text-3xl font-bold text-green-400">
                        Rs. {{ splitAmount.toLocaleString() }}
                    </p>
                </div>
            </div>
            
            <template #footer>
                <div class="flex gap-3">
                    <button @click="showSplitBillModal = false" class="flex-1 btn-secondary py-3">
                        Cancel
                    </button>
                    <button @click="processSplitPayment" class="flex-1 btn-success py-3 font-bold">
                        Process Split Payment
                    </button>
                </div>
            </template>
        </Modal>
        
        <!-- Receipt Modal -->
        <Modal v-model="showReceiptModal" title="Receipt" size="md">
            <ReceiptPreview 
                v-if="paidInvoice" 
                :invoice="paidInvoice" 
            />
            <template #footer>
                <div class="flex gap-3">
                    <button @click="showReceiptModal = false" class="flex-1 btn-secondary py-3">
                        Close
                    </button>
                    <button @click="printReceipt" class="flex-1 btn-primary py-3 font-bold">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </template>
        </Modal>
        
        <!-- Open Session Modal -->
        <Modal v-model="showOpenSession" title="Open POS Session" size="sm">
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-slate-400 mb-2 block">Select Terminal</label>
                    <Dropdown 
                        v-model="sessionTerminalId" 
                        :options="terminals" 
                        label-key="name" 
                        value-key="id" 
                        placeholder="Select terminal..." 
                    />
                </div>
                <div>
                    <label class="text-sm text-slate-400 mb-2 block">Opening Cash</label>
                    <NumPad 
                        v-model="openingCash" 
                        label="Opening Cash Amount" 
                        :show-quick-amounts="true" 
                        :quick-amounts="[5000, 10000, 15000, 20000]" 
                    />
                </div>
            </div>
            <template #footer>
                <div class="flex gap-3">
                    <button @click="showOpenSession = false" class="flex-1 btn-secondary py-3">
                        Cancel
                    </button>
                    <button 
                        @click="openSession" 
                        :disabled="!sessionTerminalId || !openingCash" 
                        class="flex-1 btn-success py-3 font-bold disabled:opacity-50"
                    >
                        Open Session
                    </button>
                </div>
            </template>
        </Modal>
        
        <!-- Close Session Modal -->
        <Modal v-model="showCloseSession" title="Close POS Session" size="md">
            <div class="space-y-4">
                <!-- Session Summary -->
                <div class="bg-slate-700 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Opening Cash</span>
                        <span>Rs. {{ currentSession?.opening_cash?.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Sales</span>
                        <span class="text-green-400">Rs. {{ sessionStats.total_sales?.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Cash Sales</span>
                        <span>Rs. {{ sessionStats.cash_sales?.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Card Sales</span>
                        <span>Rs. {{ sessionStats.card_sales?.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between font-bold pt-2 border-t border-slate-600">
                        <span>Expected Cash</span>
                        <span class="text-blue-400">Rs. {{ expectedCash.toLocaleString() }}</span>
                    </div>
                </div>
                
                <div>
                    <label class="text-sm text-slate-400 mb-2 block">Actual Closing Cash</label>
                    <NumPad 
                        v-model="closingCash" 
                        label="Count your cash drawer" 
                    />
                </div>
            </div>
            <template #footer>
                <div class="flex gap-3">
                    <button @click="showCloseSession = false" class="flex-1 btn-secondary py-3">
                        Cancel
                    </button>
                    <button 
                        @click="closeSession" 
                        class="flex-1 btn-danger py-3 font-bold"
                    >
                        Close Session
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '@/stores/pos';
import { useAuthStore } from '@/stores/auth';
import { useTimeAgo } from '@vueuse/core';
import api from '@/services/api';

// Components
import SearchInput from '@/components/common/SearchInput.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Badge from '@/components/common/Badge.vue';
import Modal from '@/components/common/Modal.vue';
import NumPad from '@/components/common/NumPad.vue';
import Dropdown from '@/components/common/Dropdown.vue';
import PaymentModal from '@/components/pos/PaymentModal.vue';
import ReceiptPreview from '@/components/pos/ReceiptPreview.vue';

const posStore = usePosStore();
const authStore = useAuthStore();

// State
const loading = ref(false);
const searchQuery = ref('');
const selectedFilter = ref('completed');
const selectedOrder = ref(null);
const showPaymentModal = ref(false);
const showSplitBillModal = ref(false);
const showReceiptModal = ref(false);
const showOpenSession = ref(false);
const showCloseSession = ref(false);

const sessionTerminalId = ref(null);
const openingCash = ref('');
const closingCash = ref('');
const paidInvoice = ref(null);
const splitWays = ref(2);

const terminals = ref([
    { id: 1, name: 'Terminal 1' },
    { id: 2, name: 'Terminal 2' },
]);

// Filters
const orderFilters = [
    { label: 'To Pay', value: 'completed', count: 0 },
    { label: 'Paid', value: 'paid', count: 0 },
];

// Computed
const currentSession = computed(() => posStore.posSession);
const completedOrders = computed(() => posStore.completedOrders);

const filteredOrders = computed(() => {
    let orders = completedOrders.value;
    
    // Filter by status (completed = ready to pay, paid = history)
    if (selectedFilter.value === 'completed') {
        orders = orders.filter(o => o.status === 'completed');
    } else if (selectedFilter.value === 'paid') {
        orders = orders.filter(o => o.status === 'paid');
    }
    
    // Search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        orders = orders.filter(o => 
            o.order_number?.toLowerCase().includes(query) ||
            o.table_name?.toLowerCase().includes(query)
        );
    }
    
    return orders;
});

const taxRate = computed(() => authStore.branch?.gst_rate || 16);

// Order totals calculation (reused from posStore getters logic but for specific order)
const orderSubtotal = computed(() => {
    if (!selectedOrder.value || !selectedOrder.value.items) return 0;
    return selectedOrder.value.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
});

const orderDiscount = computed(() => {
    if (!selectedOrder.value?.discount) return 0;
    const discount = selectedOrder.value.discount;
    if (discount.type === 'percentage') {
        return orderSubtotal.value * (discount.value / 100);
    }
    return parseFloat(discount.value);
});

const orderTax = computed(() => {
    return (orderSubtotal.value - orderDiscount.value) * (taxRate.value / 100);
});

const orderTotal = computed(() => {
    return orderSubtotal.value - orderDiscount.value + orderTax.value;
});

const splitAmount = computed(() => {
    return Math.ceil(orderTotal.value / splitWays.value);
});

const sessionStats = computed(() => {
    // This would ideally come from the API/store
    return {
        total_sales: 0,
        cash_sales: 0,
        card_sales: 0
    };
});

const expectedCash = computed(() => {
    return (currentSession.value?.opening_cash || 0) + sessionStats.value.cash_sales;
});

// Methods
function formatTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatTimeAgo(date) {
    if (!date) return '';
    return useTimeAgo(date).value;
}

function calculateOrderTotal(order) {
    if (!order || !order.items) return 0;
    
    const sub = order.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
    let disc = 0;
    if (order.discount) {
        disc = order.discount.type === 'percentage' 
            ? sub * (order.discount.value / 100) 
            : parseFloat(order.discount.value);
    }
    
    const tax = (sub - disc) * (taxRate.value / 100);
    return sub - disc + tax;
}

function getOrderStatusVariant(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'paid': return 'info';
        default: return 'default';
    }
}

function selectOrder(order) {
    selectedOrder.value = order;
}

async function refreshOrders() {
    loading.value = true;
    try {
        await posStore.fetchCompletedOrders();
    } finally {
        loading.value = false;
    }
}

async function openSession() {
    loading.value = true;
    try {
        const result = await posStore.openSession(sessionTerminalId.value, openingCash.value);
        if (result.success) {
            showOpenSession.value = false;
            // toast success
        }
    } finally {
        loading.value = false;
    }
}

async function closeSession() {
    loading.value = true;
    try {
        const result = await posStore.closeSession(closingCash.value);
        if (result.success) {
            showCloseSession.value = false;
            // toast success
        }
    } finally {
        loading.value = false;
    }
}

async function processPayment(paymentData) {
    // paymentData comes from PaymentModal
    try {
        // Create invoice/payment logic
        // For now, we simulate success
        
        // This should actually call an action in posStore or paymentApi
        // const response = await paymentApi.processPayment(...)
        
        // Mock response for UI flow
        paidInvoice.value = {
            ...selectedOrder.value,
            invoice_number: 'INV-' + Date.now(),
            subtotal: orderSubtotal.value,
            discount_amount: orderDiscount.value,
            tax_amount: orderTax.value,
            total_amount: orderTotal.value,
            tax_rate: taxRate.value,
            items: selectedOrder.value.items
        };
        
        showPaymentModal.value = false;
        showReceiptModal.value = true;
        
        // Update local status
        if (selectedOrder.value) {
            selectedOrder.value.status = 'paid';
        }
        
    } catch (e) {
        console.error(e);
    }
}

async function processSplitPayment() {
    // Similar logic to processPayment but for split
    showSplitBillModal.value = false;
    // Show payment modal for first split or handle complex split flow
}

function reprintReceipt() {
    // Load invoice data for selected order
    // paidInvoice.value = ...
    showReceiptModal.value = true;
}

function printReceipt() {
    window.print();
}

onMounted(() => {
    refreshOrders();
});
</script>
