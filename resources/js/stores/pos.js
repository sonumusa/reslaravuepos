import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import { useAuthStore } from './auth';
import { useMenuStore } from './menu';
import { useOfflineDb, saveHeldOrder, getHeldOrders, deleteHeldOrder as deleteHeldOrderFromDb } from '@/services/offline-db';

export const usePosStore = defineStore('pos', () => {
    const authStore = useAuthStore();
    const menuStore = useMenuStore();

    // ═══════════════════════════════════════════════════════
    // STATE
    // ═══════════════════════════════════════════════════════
    const tables = ref([]);
    const selectedTable = ref(null);
    const currentOrder = ref(null);
    const heldOrders = ref([]);
    const completedOrders = ref([]);
    const posSession = ref(null);
    const terminals = ref([]);
    const isLoading = ref(false);

    // ═══════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════
    const availableTables = computed(() =>
        tables.value.filter(t => t.status === 'available')
    );

    const occupiedTables = computed(() =>
        tables.value.filter(t => t.status === 'occupied')
    );

    const hasActiveOrder = computed(() =>
        currentOrder.value && currentOrder.value.items?.length > 0
    );

    const orderItems = computed(() =>
        currentOrder.value?.items || []
    );

    const orderSubtotal = computed(() =>
        orderItems.value.reduce((sum, item) => sum + (item.subtotal || 0), 0)
    );

    const orderTax = computed(() => {
        const taxRate = authStore.branch?.gst_rate || 16;
        return Math.round(orderSubtotal.value * (taxRate / 100));
    });

    const orderDiscount = computed(() => {
        if (!currentOrder.value?.discount) return 0;

        const discount = currentOrder.value.discount;
        if (discount.type === 'percentage') {
            return Math.round(orderSubtotal.value * (discount.value / 100));
        }
        return discount.value;
    });

    const orderTotal = computed(() =>
        orderSubtotal.value - orderDiscount.value + orderTax.value
    );

    const heldOrdersCount = computed(() => heldOrders.value.length);

    const hasSession = computed(() => !!posSession.value);

    // ═══════════════════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════════════════

    /**
     * Fetch terminals
     */
    async function fetchTerminals() {
        try {
            const response = await api.get('/terminals');
            terminals.value = response.data.data || [];
            return terminals.value;
        } catch (e) {
            console.error('Failed to fetch terminals:', e);
            return [];
        }
    }

    /**
     * Fetch tables
     */
    async function fetchTables() {
        try {
            const response = await api.get('/tables', {
                params: { with_order: true }
            });
            tables.value = response.data.data || [];
        } catch (e) {
            console.error('Failed to fetch tables:', e);
        }
    }

    /**
     * Select table and start new order
     */
    function selectTable(table) {
        selectedTable.value = table;

        // Check if table has active order
        if (table.active_order) {
            loadOrder(table.active_order);
        } else {
            startNewOrder(table);
        }
    }

    /**
     * Start a new order
     */
    function startNewOrder(table) {
        currentOrder.value = {
            uuid: generateUUID(),
            table_id: table.id,
            table_name: table.name,
            table: table,
            customer_id: null,
            customer_name: 'Walk-in',
            customer: null,
            items: [],
            status: 'draft',
            order_number: 'NEW',
            notes: '',
            kitchen_notes: '',
            discount: null,
            created_at: new Date().toISOString(),
            created_offline: !navigator.onLine,
        };
    }

    /**
     * Load existing order
     */
    function loadOrder(order) {
        currentOrder.value = {
            ...order,
            items: order.items || [],
        };
    }

    /**
     * Add item to order
     */
    function addItem(menuItem, quantity = 1, modifiers = [], notes = '') {
        if (!currentOrder.value) {
            console.warn('No current order to add item to');
            return;
        }

        const price = menuStore.getCurrentPrice ? menuStore.getCurrentPrice(menuItem.id) : menuItem.price;
        const modifierTotal = modifiers.reduce((sum, m) => sum + (parseFloat(m.price) || 0), 0);
        const unitPrice = parseFloat(price) + modifierTotal;

        // Check if same item with same modifiers exists
        const existingIndex = currentOrder.value.items.findIndex(item =>
            item.menu_item_id === menuItem.id &&
            JSON.stringify(item.modifiers) === JSON.stringify(modifiers) &&
            item.notes === notes
        );

        if (existingIndex >= 0 && !notes) {
            // Increment quantity
            currentOrder.value.items[existingIndex].quantity += quantity;
            currentOrder.value.items[existingIndex].subtotal =
                currentOrder.value.items[existingIndex].quantity * unitPrice;
        } else {
            // Add new item
            currentOrder.value.items.push({
                id: Date.now(),
                menu_item_id: menuItem.id,
                item_name: menuItem.name,
                unit_price: unitPrice,
                quantity: quantity,
                subtotal: unitPrice * quantity,
                modifiers: modifiers.map(m => ({
                    menu_modifier_id: m.id,
                    modifier_name: m.name,
                    group_name: m.group_name,
                    price: m.price,
                })),
                notes: notes,
                status: 'pending',
            });
        }

        // Update order status to open if it was draft
        if (currentOrder.value.status === 'draft') {
            currentOrder.value.status = 'open';
        }
    }

    /**
     * Update item quantity
     */
    function updateItemQuantity(itemIndex, quantity) {
        if (!currentOrder.value || !currentOrder.value.items[itemIndex]) return;

        if (quantity <= 0) {
            removeItem(itemIndex);
            return;
        }

        const item = currentOrder.value.items[itemIndex];
        item.quantity = quantity;
        item.subtotal = item.unit_price * quantity;
    }

    /**
     * Remove item from order
     */
    function removeItem(itemIndex) {
        if (!currentOrder.value) return;
        currentOrder.value.items.splice(itemIndex, 1);
    }

    /**
     * Apply discount
     */
    function applyDiscount(type, value, reason = '') {
        if (!currentOrder.value) return;

        currentOrder.value.discount = {
            type,
            value: parseFloat(value),
            reason,
        };
    }

    /**
     * Remove discount
     */
    function removeDiscount() {
        if (!currentOrder.value) return;
        currentOrder.value.discount = null;
    }

    /**
     * Set order notes
     */
    function setOrderNotes(notes) {
        if (!currentOrder.value) return;
        currentOrder.value.notes = notes;
    }

    /**
     * Set kitchen notes
     */
    function setKitchenNotes(notes) {
        if (!currentOrder.value) return;
        currentOrder.value.kitchen_notes = notes;
    }

    // ═══════════════════════════════════════════════════════
    // HOLD ORDER - Fixed
    // ═══════════════════════════════════════════════════════

    /**
     * Hold current order
     */
    async function holdOrder() {
        if (!currentOrder.value || !currentOrder.value.items || currentOrder.value.items.length === 0) {
            console.warn('No order or no items to hold');
            return false;
        }

        try {
            // Create a copy with hold status
            const orderToHold = {
                ...currentOrder.value,
                status: 'hold',
                held_at: new Date().toISOString(),
            };

            // Add to held orders array
            heldOrders.value.push(orderToHold);

            // Save to IndexedDB for persistence
            try {
                await saveHeldOrder(orderToHold);
                console.log('Order saved to IndexedDB');
            } catch (dbError) {
                console.warn('Failed to save to IndexedDB, order held in memory only:', dbError);
            }

            // Clear current order AFTER saving
            currentOrder.value = null;
            selectedTable.value = null;

            console.log('Order held successfully. Total held:', heldOrders.value.length);
            return true;
        } catch (error) {
            console.error('Failed to hold order:', error);
            return false;
        }
    }

    /**
     * Resume held order
     */
    async function resumeOrder(orderUuid) {
        const index = heldOrders.value.findIndex(o => o.uuid === orderUuid);
        if (index < 0) {
            console.warn('Held order not found:', orderUuid);
            return false;
        }

        try {
            // Get the order
            const orderToResume = { ...heldOrders.value[index] };
            orderToResume.status = 'open';
            delete orderToResume.held_at;

            // Set as current order
            currentOrder.value = orderToResume;

            // Set selected table
            selectedTable.value = tables.value.find(t => t.id === orderToResume.table_id) || null;

            // Remove from held orders
            heldOrders.value.splice(index, 1);

            // Remove from IndexedDB
            try {
                await deleteHeldOrderFromDb(orderUuid);
            } catch (dbError) {
                console.warn('Failed to remove from IndexedDB:', dbError);
            }

            console.log('Order resumed successfully');
            return true;
        } catch (error) {
            console.error('Failed to resume order:', error);
            return false;
        }
    }

    /**
     * Delete held order
     */
    async function deleteHeldOrder(orderUuid) {
        const index = heldOrders.value.findIndex(o => o.uuid === orderUuid);
        if (index < 0) {
            console.warn('Held order not found:', orderUuid);
            return false;
        }

        try {
            // Remove from array
            heldOrders.value.splice(index, 1);

            // Remove from IndexedDB
            try {
                await deleteHeldOrderFromDb(orderUuid);
            } catch (dbError) {
                console.warn('Failed to remove from IndexedDB:', dbError);
            }

            console.log('Held order deleted');
            return true;
        } catch (error) {
            console.error('Failed to delete held order:', error);
            return false;
        }
    }

    /**
     * Load held orders from IndexedDB
     */
    async function loadHeldOrders() {
        try {
            const orders = await getHeldOrders();
            heldOrders.value = orders || [];
            console.log('Loaded held orders:', heldOrders.value.length);
            return heldOrders.value;
        } catch (error) {
            console.warn('Failed to load held orders from IndexedDB:', error);
            heldOrders.value = [];
            return [];
        }
    }

    /**
     * Clear current order
     */
    function clearCurrentOrder() {
        currentOrder.value = null;
        selectedTable.value = null;
    }

    /**
     * Send order to kitchen
     */
    async function sendToKitchen() {
        if (!currentOrder.value) return { success: false, error: 'No current order' };

        if (!navigator.onLine) {
            return { success: false, error: 'Offline mode not supported for kitchen sync yet' };
        }

        try {
            // If order doesn't have an ID, create it first
            if (!currentOrder.value.id) {
                const createResponse = await api.post('/orders', {
                    uuid: currentOrder.value.uuid,
                    table_id: currentOrder.value.table_id,
                    customer_id: currentOrder.value.customer_id,
                    order_type: 'dine_in',
                    notes: currentOrder.value.notes,
                    kitchen_notes: currentOrder.value.kitchen_notes,
                    is_priority: currentOrder.value.is_priority || false,
                    items: currentOrder.value.items.map(item => ({
                        menu_item_id: item.menu_item_id,
                        quantity: item.quantity,
                        notes: item.notes,
                        modifiers: item.modifiers?.map(m => ({
                            menu_modifier_id: m.menu_modifier_id
                        })) || []
                    }))
                });

                if (createResponse.data.success) {
                    const newOrder = createResponse.data.data;
                    currentOrder.value.id = newOrder.id;
                    currentOrder.value.order_number = newOrder.order_number;
                    if (newOrder.items?.length > 0) {
                        currentOrder.value.items = newOrder.items;
                    }
                } else {
                    throw new Error(createResponse.data.message || 'Failed to create order');
                }
            }

            // Now send to kitchen
            if (currentOrder.value.id) {
                await api.post(`/orders/${currentOrder.value.id}/send-kitchen`);

                currentOrder.value.status = 'sent_to_kitchen';
                currentOrder.value.sent_to_kitchen_at = new Date().toISOString();

                // Update item statuses
                currentOrder.value.items.forEach(item => {
                    if (item.status === 'pending') {
                        item.status = 'sent';
                    }
                });

                return { success: true, data: currentOrder.value };
            }

            return { success: false, error: 'Order ID missing' };
        } catch (e) {
            console.error('Failed to send to kitchen:', e);
            return { success: false, error: e.response?.data?.message || e.message };
        }
    }

    /**
     * Complete order (ready for payment)
     */
    async function completeOrder() {
        if (!currentOrder.value) return { success: false, error: 'No current order' };

        try {
            currentOrder.value.status = 'completed';
            currentOrder.value.completed_at = new Date().toISOString();

            if (navigator.onLine && currentOrder.value.id) {
                await api.post(`/orders/${currentOrder.value.id}/complete`);
            } else if (navigator.onLine && !currentOrder.value.id) {
                // Create and complete
                const response = await api.post('/orders', {
                    ...currentOrder.value,
                    items: currentOrder.value.items.map(item => ({
                        menu_item_id: item.menu_item_id,
                        quantity: item.quantity,
                        notes: item.notes,
                        modifiers: item.modifiers?.map(m => ({
                            menu_modifier_id: m.menu_modifier_id
                        })) || []
                    }))
                });

                if (response.data.success) {
                    currentOrder.value.id = response.data.data.id;
                    await api.post(`/orders/${currentOrder.value.id}/complete`);
                }
            }

            // Add to completed orders
            completedOrders.value.push({ ...currentOrder.value });

            // Update table status
            const table = tables.value.find(t => t.id === currentOrder.value.table_id);
            if (table) {
                table.status = 'occupied';
            }

            clearCurrentOrder();

            return { success: true };
        } catch (e) {
            console.error('Failed to complete order:', e);
            return { success: false, error: e.message };
        }
    }

    /**
     * Fetch completed orders
     */
    async function fetchCompletedOrders() {
        try {
            const response = await api.get('/orders/completed');
            completedOrders.value = response.data.data || [];
        } catch (e) {
            console.error('Failed to fetch completed orders:', e);
        }
    }

    /**
     * Check active session
     */
    async function checkActiveSession() {
        return fetchCurrentSession();
    }

    /**
     * Refresh session
     */
    async function refreshSession() {
        return fetchCurrentSession();
    }

    /**
     * Fetch current session
     */
    async function fetchCurrentSession() {
        try {
            const response = await api.get('/pos-sessions/current');
            posSession.value = response.data.data;
            return posSession.value;
        } catch (e) {
            console.error('Failed to fetch current session:', e);
            return null;
        }
    }

    /**
     * Open POS session
     */
    async function openSession(terminalId, openingCash) {
        try {
            const response = await api.post('/pos-sessions/open', {
                terminal_id: terminalId,
                opening_cash: openingCash,
            });

            if (response.data.success) {
                posSession.value = response.data.data;
                return { success: true, session: posSession.value };
            }

            return { success: false, error: response.data.message };
        } catch (e) {
            return { success: false, error: e.response?.data?.message || e.message };
        }
    }

    /**
     * Close POS session
     */
    async function closeSession(closingCash) {
        try {
            const response = await api.post('/pos-sessions/close', {
                closing_cash: closingCash,
            });

            if (response.data.success) {
                const summary = response.data.data;
                posSession.value = null;
                return { success: true, summary };
            }

            return { success: false, error: response.data.message };
        } catch (e) {
            return { success: false, error: e.response?.data?.message || e.message };
        }
    }

    /**
     * Generate UUID
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // ═══════════════════════════════════════════════════════
    // RETURN
    // ═══════════════════════════════════════════════════════
    return {
        // State
        tables,
        selectedTable,
        currentOrder,
        heldOrders,
        completedOrders,
        posSession,
        terminals,
        isLoading,

        // Getters
        availableTables,
        occupiedTables,
        hasActiveOrder,
        orderItems,
        orderSubtotal,
        orderTax,
        orderDiscount,
        orderTotal,
        heldOrdersCount,
        hasSession,

        // Actions
        fetchTables,
        fetchTerminals,
        selectTable,
        startNewOrder,
        loadOrder,
        addItem,
        updateItemQuantity,
        removeItem,
        applyDiscount,
        removeDiscount,
        setOrderNotes,
        setKitchenNotes,
        holdOrder,
        resumeOrder,
        deleteHeldOrder,
        loadHeldOrders,
        clearCurrentOrder,
        sendToKitchen,
        completeOrder,
        fetchCompletedOrders,
        fetchCurrentSession,
        checkActiveSession,
        refreshSession,
        openSession,
        closeSession,
    };
}, {
    persist: {
        key: 'pos',
        paths: ['heldOrders'],
        storage: localStorage
    }
});