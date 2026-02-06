<template>
    <div class="h-full flex">
        <!-- Left Panel - Menu Items -->
        <div class="flex-1 flex flex-col bg-slate-900">
            <!-- Table Selection Bar -->
            <div class="bg-slate-800 px-4 py-3 flex items-center gap-4 border-b border-slate-700">
                <button
                    @click="goToTableSelection"
                    class="btn-touch flex items-center gap-2 bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl font-medium"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    <i class="fas fa-chair"></i>
                    <span>{{ currentTableName }}</span>
                </button>

                <div class="flex-1"></div>

                <!-- Held Orders Button -->
                <button
                    @click="showHeldOrders = true"
                    class="btn-touch flex items-center gap-2 bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 px-4 py-2 rounded-xl font-medium relative"
                >
                    <i class="fas fa-pause-circle"></i>
                    <span>Held Orders</span>
                    <span
                        v-if="heldOrdersCount > 0"
                        class="absolute -top-1 -right-1 w-5 h-5 bg-amber-500 text-white text-xs rounded-full flex items-center justify-center font-bold"
                    >
                        {{ heldOrdersCount }}
                    </span>
                </button>

                <!-- Daily Specials -->
                <button
                    @click="showSpecials = true"
                    class="btn-touch flex items-center gap-2 bg-pink-500/20 text-pink-400 hover:bg-pink-500/30 px-4 py-2 rounded-xl font-medium"
                >
                    <i class="fas fa-star"></i>
                    <span class="hidden lg:inline">Today's Special</span>
                </button>
            </div>

            <!-- Category Tabs -->
            <CategoryTabs
                :categories="categories"
                :selected-id="selectedCategoryId"
                @select="selectCategory"
            />

            <!-- Search Bar -->
            <div class="px-4 py-3">
                <SearchInput
                    v-model="searchQuery"
                    placeholder="Search menu items..."
                    @search="searchMenuItems"
                    @clear="clearSearch"
                />
            </div>

            <!-- Menu Grid -->
            <MenuGrid
                :items="filteredMenuItems"
                :loading="loadingMenu"
                @select="handleItemSelect"
            />
        </div>

        <!-- Right Panel - Order Summary -->
        <OrderPanel
            :order="safeCurrentOrder"
            :editable="canEditOrder"
            :tax-rate="taxRate"
            @increment="incrementItem"
            @decrement="decrementItem"
            @remove="removeItem"
            @edit-item="editOrderItem"
            @add-notes="showOrderNotesModal = true"
            @apply-discount="showDiscountModal = true"
        >
            <template #actions>
                <!-- Waiter Action Buttons -->
                <div class="grid grid-cols-2 gap-2">
                    <button
                        @click="holdAndGoBack"
                        :disabled="!canHoldOrder"
                        class="btn-touch bg-amber-500 hover:bg-amber-400 text-slate-900 rounded-xl py-3 font-bold disabled:opacity-50"
                    >
                        <i class="fas fa-pause mr-2"></i>HOLD
                    </button>
                    <button
                        @click="sendToKitchen"
                        :disabled="!canSendToKitchen"
                        class="btn-touch bg-blue-500 hover:bg-blue-400 text-white rounded-xl py-3 font-bold disabled:opacity-50"
                    >
                        <i class="fas fa-kitchen-set mr-2"></i>KDS
                    </button>
                </div>
                <button
                    @click="completeOrder"
                    :disabled="!canCompleteOrder"
                    class="w-full btn-touch bg-green-600 hover:bg-green-500 text-white rounded-xl py-4 font-bold text-lg disabled:opacity-50"
                >
                    <i class="fas fa-check-circle mr-2"></i>MARK COMPLETED
                </button>
                <button
                    @click="confirmClearOrder"
                    :disabled="!hasItems"
                    class="w-full btn-touch bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-xl py-3 font-medium disabled:opacity-50"
                >
                    <i class="fas fa-trash mr-2"></i>Clear Order
                </button>
            </template>
        </OrderPanel>

        <!-- Table Selector Modal -->
        <TableSelectorModal
            v-model="showTableSelector"
            :tables="tables"
            @select="selectTable"
        />

        <!-- Modifier Modal -->
        <ModifierModal
            v-model="showModifierModal"
            :item="selectedMenuItem"
            :modifiers="selectedItemModifiers"
            @add="addItemToOrder"
        />

        <!-- Discount Modal -->
        <DiscountModal
            v-model="showDiscountModal"
            :current-discount="currentOrder?.discount"
            @apply="applyDiscount"
            @clear="clearDiscount"
        />

        <!-- Held Orders List -->
        <HeldOrdersList
            v-model="showHeldOrders"
            :orders="heldOrders"
            @resume="resumeOrder"
            @delete="deleteHeldOrder"
        />

        <!-- Order Notes Modal -->
        <Modal v-model="showOrderNotesModal" title="Order Notes" size="sm">
            <textarea
                v-model="orderNotes"
                class="input-field resize-none w-full bg-slate-700 text-white rounded-lg p-3"
                rows="4"
                placeholder="Enter special instructions..."
            ></textarea>
            <template #footer>
                <div class="flex gap-3">
                    <button @click="showOrderNotesModal = false" class="flex-1 btn-secondary py-3">
                        Cancel
                    </button>
                    <button @click="saveOrderNotes" class="flex-1 btn-primary py-3">
                        Save Notes
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Confirm Clear Modal -->
        <ConfirmDialog
            v-model="showClearConfirm"
            type="danger"
            title="Clear Order?"
            message="This will remove all items from the current order. This cannot be undone."
            confirm-text="Clear Order"
            @confirm="clearOrder"
        />

        <!-- Confirm Back Modal (Hold or Discard) -->
        <ConfirmDialog
            v-model="showBackConfirm"
            type="warning"
            title="Save Order?"
            message="You have items in your order. Would you like to hold this order before leaving?"
            confirm-text="Hold Order"
            cancel-text="Discard"
            @confirm="holdAndNavigateBack"
            @cancel="discardAndNavigateBack"
        />

        <!-- Daily Specials Modal -->
        <Modal v-model="showSpecials" title="Today's Specials" size="lg">
            <div class="grid grid-cols-2 gap-4">
                <div
                    v-for="special in dailySpecials"
                    :key="special.id"
                    class="bg-gradient-to-br from-pink-500/20 to-purple-500/20 rounded-xl p-4 border border-pink-500/30"
                >
                    <div class="flex items-start justify-between mb-2">
                        <h4 class="font-bold">{{ special.title }}</h4>
                        <span class="bg-pink-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                            {{ Math.round((1 - special.special_price / special.original_price) * 100) }}% OFF
                        </span>
                    </div>
                    <p class="text-sm text-slate-400 mb-3">{{ special.menu_item?.name }}</p>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 line-through text-sm">Rs. {{ special.original_price }}</span>
                            <span class="text-green-400 font-bold text-lg ml-2">Rs. {{ special.special_price }}</span>
                        </div>
                        <button
                            @click="addSpecialToOrder(special)"
                            class="btn-touch bg-pink-500 hover:bg-pink-400 px-4 py-2 rounded-lg font-medium"
                        >
                            Add
                        </button>
                    </div>
                </div>
            </div>
            <EmptyState
                v-if="dailySpecials.length === 0"
                icon="fas fa-star"
                title="No Specials Today"
                message="Check back later for special offers!"
            />
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { usePosStore } from '@/stores/pos';
import { useMenuStore } from '@/stores/menu';
import { useAuthStore } from '@/stores/auth';

// Components
import CategoryTabs from '@/components/pos/CategoryTabs.vue';
import MenuGrid from '@/components/pos/MenuGrid.vue';
import OrderPanel from '@/components/pos/OrderPanel.vue';
import TableSelectorModal from '@/components/pos/TableSelectorModal.vue';
import ModifierModal from '@/components/pos/ModifierModal.vue';
import DiscountModal from '@/components/pos/DiscountModal.vue';
import HeldOrdersList from '@/components/pos/HeldOrdersList.vue';
import SearchInput from '@/components/common/SearchInput.vue';
import Modal from '@/components/common/Modal.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import EmptyState from '@/components/common/EmptyState.vue';

const router = useRouter();
const posStore = usePosStore();
const menuStore = useMenuStore();
const authStore = useAuthStore();

// ═══════════════════════════════════════════════════════
// REACTIVE STATE
// ═══════════════════════════════════════════════════════
const showTableSelector = ref(false);
const showModifierModal = ref(false);
const showDiscountModal = ref(false);
const showHeldOrders = ref(false);
const showOrderNotesModal = ref(false);
const showClearConfirm = ref(false);
const showBackConfirm = ref(false);
const showSpecials = ref(false);

const selectedCategoryId = ref(null);
const searchQuery = ref('');
const loadingMenu = ref(false);
const selectedMenuItem = ref(null);
const selectedItemModifiers = ref([]);
const orderNotes = ref('');

// ═══════════════════════════════════════════════════════
// COMPUTED - With Null Safety
// ═══════════════════════════════════════════════════════
const categories = computed(() => menuStore.categories || []);
const menuItems = computed(() => menuStore.menuItems || []);
const tables = computed(() => posStore.tables || []);
const currentOrder = computed(() => posStore.currentOrder);
const heldOrders = computed(() => posStore.heldOrders || []);
const heldOrdersCount = computed(() => heldOrders.value.length);
const dailySpecials = computed(() => menuStore.dailySpecials || []);
const taxRate = computed(() => authStore.branch?.gst_rate || 16);

// Safe order object for OrderPanel (never null)
const safeCurrentOrder = computed(() => {
    if (currentOrder.value) {
        return currentOrder.value;
    }
    // Return empty order structure
    return {
        status: 'draft',
        order_number: 'NEW',
        table: null,
        table_name: 'No Table',
        customer: null,
        items: [],
        notes: '',
        discount: null
    };
});

const currentTableName = computed(() => {
    return currentOrder.value?.table_name || currentOrder.value?.table?.name || 'Select Table';
});

const hasItems = computed(() => {
    return currentOrder.value?.items?.length > 0;
});

const filteredMenuItems = computed(() => {
    let items = menuItems.value;

    // Filter by category
    if (selectedCategoryId.value && selectedCategoryId.value !== 'all') {
        items = items.filter(item => item.category_id === selectedCategoryId.value);
    }

    // Filter by search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        items = items.filter(item =>
            item.name.toLowerCase().includes(query) ||
            item.short_name?.toLowerCase().includes(query) ||
            item.sku?.toLowerCase().includes(query)
        );
    }

    // Only available items
    return items.filter(item => item.is_available);
});

const canEditOrder = computed(() => {
    return currentOrder.value && ['draft', 'open', 'hold'].includes(currentOrder.value.status);
});

const canHoldOrder = computed(() => {
    return currentOrder.value &&
           currentOrder.value.items?.length > 0 &&
           ['draft', 'open'].includes(currentOrder.value.status);
});

const canSendToKitchen = computed(() => {
    return currentOrder.value &&
           currentOrder.value.items?.length > 0 &&
           currentOrder.value.table_id &&
           ['open', 'hold', 'draft'].includes(currentOrder.value.status);
});

const canCompleteOrder = computed(() => {
    return currentOrder.value &&
           currentOrder.value.items?.length > 0 &&
           currentOrder.value.table_id;
});

// ═══════════════════════════════════════════════════════
// METHODS
// ═══════════════════════════════════════════════════════

function selectCategory(categoryId) {
    selectedCategoryId.value = categoryId;
    menuStore.selectCategory(categoryId);
}

function searchMenuItems(query) {
    searchQuery.value = query;
}

function clearSearch() {
    searchQuery.value = '';
}

function selectTable(table) {
    console.log('Table selected:', table);
    posStore.selectTable(table);
    showTableSelector.value = false;
}

function goToTableSelection() {
    // If order has items, ask to hold
    if (hasItems.value) {
        showBackConfirm.value = true;
    } else {
        router.push('/pos/tables');
    }
}

async function holdAndNavigateBack() {
    showBackConfirm.value = false;
    await holdOrder();
    router.push('/pos/tables');
}

function discardAndNavigateBack() {
    showBackConfirm.value = false;
    posStore.clearCurrentOrder();
    router.push('/pos/tables');
}

async function handleItemSelect(item) {
    console.log('Item selected:', item);
    
    // Check if table is selected first
    if (!currentOrder.value || !currentOrder.value.table_id) {
        showTableSelector.value = true;
        return;
    }

    // Check if item has modifiers
    if (item.modifiers && item.modifiers.length > 0) {
        selectedMenuItem.value = item;
        selectedItemModifiers.value = item.modifiers;
        showModifierModal.value = true;
    } else {
        // Add directly to order
        addItemToOrder({
            menuItem: item,
            quantity: 1,
            modifiers: [],
            notes: '',
        });
    }
}

function addItemToOrder(itemData) {
    posStore.addItem(itemData.menuItem, itemData.quantity, itemData.modifiers, itemData.notes);
    showModifierModal.value = false;
}

function incrementItem(item) {
    const idx = currentOrder.value?.items?.findIndex(i => i === item);
    if (idx >= 0) {
        posStore.updateItemQuantity(idx, item.quantity + 1);
    }
}

function decrementItem(item) {
    const idx = currentOrder.value?.items?.findIndex(i => i === item);
    if (idx >= 0) {
        if (item.quantity > 1) {
            posStore.updateItemQuantity(idx, item.quantity - 1);
        } else {
            removeItem(item);
        }
    }
}

function removeItem(item) {
    const idx = currentOrder.value?.items?.findIndex(i => i === item);
    if (idx >= 0) {
        posStore.removeItem(idx);
    }
}

function editOrderItem(item) {
    const menuItem = menuStore.getItemById(item.menu_item_id);
    if (menuItem) {
        selectedMenuItem.value = menuItem;
        selectedItemModifiers.value = menuItem.modifiers || [];
        showModifierModal.value = true;
    }
}

function applyDiscount(discount) {
    posStore.applyDiscount(discount.type, discount.value, discount.reason);
    showDiscountModal.value = false;
}

function clearDiscount() {
    posStore.removeDiscount();
}

// ═══════════════════════════════════════════════════════
// HOLD ORDER - Fixed
// ═══════════════════════════════════════════════════════
async function holdOrder() {
    if (!canHoldOrder.value) return;
    
    try {
        console.log('Holding order...');
        await posStore.holdOrder();
        console.log('Order held successfully');
        return true;
    } catch (error) {
        console.error('Failed to hold order:', error);
        return false;
    }
}

async function holdAndGoBack() {
    const success = await holdOrder();
    if (success) {
        router.push('/pos/tables');
    }
}

async function resumeOrder(orderUuid) {
    try {
        console.log('Resuming order:', orderUuid);
        await posStore.resumeOrder(orderUuid);
        showHeldOrders.value = false;
    } catch (error) {
        console.error('Failed to resume order:', error);
    }
}

async function deleteHeldOrder(order) {
    try {
        await posStore.deleteHeldOrder(order.uuid);
    } catch (error) {
        console.error('Failed to delete held order:', error);
    }
}

async function sendToKitchen() {
    try {
        const result = await posStore.sendToKitchen();
        if (result?.success) {
            console.log('Order sent to kitchen');
        }
    } catch (error) {
        console.error('Failed to send to kitchen:', error);
    }
}

async function completeOrder() {
    try {
        const result = await posStore.completeOrder();
        if (result?.success) {
            console.log('Order completed');
            router.push('/pos/tables');
        }
    } catch (error) {
        console.error('Failed to complete order:', error);
    }
}

function confirmClearOrder() {
    showClearConfirm.value = true;
}

function clearOrder() {
    posStore.clearCurrentOrder();
    showClearConfirm.value = false;
}

function saveOrderNotes() {
    if (currentOrder.value) {
        posStore.setOrderNotes(orderNotes.value);
    }
    showOrderNotesModal.value = false;
}

function addSpecialToOrder(special) {
    if (!special.menu_item) return;
    handleItemSelect(special.menu_item);
    showSpecials.value = false;
}

// ═══════════════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════════════
onMounted(async () => {
    loadingMenu.value = true;

    try {
        await Promise.all([
            menuStore.fetchMenu(),
            posStore.fetchTables(),
            posStore.loadHeldOrders(),
        ]);
    } catch (error) {
        console.error('Failed to load POS data:', error);
    } finally {
        loadingMenu.value = false;
    }
});

// Watch for order notes changes
watch(() => currentOrder.value?.notes, (newNotes) => {
    orderNotes.value = newNotes || '';
});
</script>