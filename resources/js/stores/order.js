import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import api from '@/services/api';
import { useAuthStore } from './auth';
import { useAppStore } from './app';
import { usePosSessionStore } from './posSession';
import { useOfflineDb } from '@/services/offline-db';

export const useOrderStore = defineStore('order', () => {
    const offlineDb = useOfflineDb();

    // State
    const orders = ref([]);
    const currentOrder = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    // Getters
    const activeOrders = computed(() => 
        orders.value.filter(o => !['paid', 'cancelled', 'void'].includes(o.status))
    );

    const completedOrders = computed(() => 
        orders.value.filter(o => o.status === 'paid')
    );

    const kitchenOrders = computed(() => 
        orders.value.filter(o => ['sent_to_kitchen', 'preparing', 'ready'].includes(o.status))
    );

    const ordersByTable = computed(() => {
        const grouped = {};
        orders.value.forEach(order => {
            if (order.table_id && !['paid', 'cancelled', 'void'].includes(order.status)) {
                grouped[order.table_id] = order;
            }
        });
        return grouped;
    });

    const currentOrderItems = computed(() => 
        currentOrder.value?.items || []
    );

    const currentOrderSubtotal = computed(() => {
        if (!currentOrder.value?.items) return 0;
        return currentOrder.value.items.reduce((sum, item) => {
            if (!item.is_void) {
                return sum + parseFloat(item.subtotal || 0);
            }
            return sum;
        }, 0);
    });

    const currentOrderItemCount = computed(() => {
        if (!currentOrder.value?.items) return 0;
        return currentOrder.value.items.reduce((count, item) => {
            if (!item.is_void) {
                return count + item.quantity;
            }
            return count;
        }, 0);
    });

    // ═══════════════════════════════════════════════════════
    // ACTIONS - All API paths fixed (removed /api prefix)
    // ═══════════════════════════════════════════════════════

    /**
     * Fetch all orders
     */
    async function fetchOrders(params = {}) {
        const authStore = useAuthStore();
        
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders' to '/orders'
            const response = await api.get('/orders', {
                params: {
                    branch_id: authStore.branchId,
                    ...params
                }
            });

            if (response.data.success) {
                orders.value = response.data.data;
                return orders.value;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Fetch orders error:', err);
            
            // Load from offline
            await loadOrdersFromOffline();
            return orders.value;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Fetch single order by ID
     */
    async function fetchOrder(id) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/${id}' to '/orders/${id}'
            const response = await api.get(`/orders/${id}`);
            
            if (response.data.success) {
                const order = response.data.data;
                
                // Update in orders array
                const index = orders.value.findIndex(o => o.id === id);
                if (index !== -1) {
                    orders.value[index] = order;
                }
                
                return order;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Fetch order error:', err);
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Create new order (local only, not saved to server yet)
     */
    function createNewOrder(data = {}) {
        const authStore = useAuthStore();
        const sessionStore = usePosSessionStore();

        currentOrder.value = {
            uuid: uuidv4(),
            branch_id: authStore.branchId,
            terminal_id: sessionStore.terminalId,
            table_id: data.table_id || null,
            customer_id: data.customer_id || null,
            waiter_id: authStore.user?.id,
            order_type: data.order_type || 'dine_in',
            status: 'draft',
            guest_count: data.guest_count || 1,
            notes: data.notes || '',
            kitchen_notes: data.kitchen_notes || '',
            is_priority: false,
            items: [],
            created_at: new Date().toISOString(),
            created_offline: false
        };

        return currentOrder.value;
    }

    /**
     * Add item to current order
     */
    function addItemToOrder(menuItem, quantity = 1, modifiers = [], notes = '') {
        if (!currentOrder.value) {
            createNewOrder();
        }

        const modifiersTotal = modifiers.reduce((sum, mod) => sum + parseFloat(mod.price || 0), 0);
        const unitPrice = parseFloat(menuItem.price);
        const subtotal = (unitPrice + modifiersTotal) * quantity;

        const orderItem = {
            id: uuidv4(),
            menu_item_id: menuItem.id,
            item_name: menuItem.name,
            unit_price: unitPrice,
            quantity: quantity,
            subtotal: subtotal,
            notes: notes,
            status: 'pending',
            is_void: false,
            modifiers: modifiers.map(mod => ({
                menu_modifier_id: mod.id,
                modifier_name: mod.name,
                group_name: mod.group_name,
                price: parseFloat(mod.price || 0)
            }))
        };

        currentOrder.value.items.push(orderItem);
        return orderItem;
    }

    /**
     * Update item quantity
     */
    function updateItemQuantity(itemId, quantity) {
        if (!currentOrder.value) return;

        const item = currentOrder.value.items.find(i => i.id === itemId);
        if (item && quantity > 0) {
            item.quantity = quantity;
            const modifiersTotal = item.modifiers?.reduce((sum, mod) => sum + parseFloat(mod.price || 0), 0) || 0;
            item.subtotal = (item.unit_price + modifiersTotal) * quantity;
        } else if (item && quantity <= 0) {
            removeItemFromOrder(itemId);
        }
    }

    /**
     * Remove item from order
     */
    function removeItemFromOrder(itemId) {
        if (!currentOrder.value) return;
        currentOrder.value.items = currentOrder.value.items.filter(i => i.id !== itemId);
    }

    /**
     * Clear current order
     */
    function clearCurrentOrder() {
        currentOrder.value = null;
    }

    /**
     * Save order to server
     */
    async function saveOrder() {
        if (!currentOrder.value) {
            return { success: false, error: 'No current order' };
        }

        const appStore = useAppStore();
        isLoading.value = true;
        error.value = null;

        // If offline, save locally
        if (!appStore.isOnline) {
            return await saveOrderOffline();
        }

        try {
            const orderData = {
                uuid: currentOrder.value.uuid,
                branch_id: currentOrder.value.branch_id,
                terminal_id: currentOrder.value.terminal_id,
                table_id: currentOrder.value.table_id,
                customer_id: currentOrder.value.customer_id,
                waiter_id: currentOrder.value.waiter_id,
                order_type: currentOrder.value.order_type,
                guest_count: currentOrder.value.guest_count,
                notes: currentOrder.value.notes,
                kitchen_notes: currentOrder.value.kitchen_notes,
                is_priority: currentOrder.value.is_priority,
                items: currentOrder.value.items.map(item => ({
                    menu_item_id: item.menu_item_id,
                    quantity: item.quantity,
                    notes: item.notes,
                    modifiers: item.modifiers?.map(mod => ({
                        menu_modifier_id: mod.menu_modifier_id
                    })) || []
                }))
            };

            // ✅ FIXED: Changed from '/api/orders' to '/orders'
            const response = await api.post('/orders', orderData);

            if (response.data.success) {
                const savedOrder = response.data.data;
                orders.value.unshift(savedOrder);
                currentOrder.value = savedOrder;
                return { success: true, data: savedOrder };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Save order error:', err);
            
            // Fall back to offline save
            if (err.message?.includes('Network Error')) {
                return await saveOrderOffline();
            }
            
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Save order offline
     */
    async function saveOrderOffline() {
        const appStore = useAppStore();

        try {
            currentOrder.value.created_offline = true;
            currentOrder.value.synced = false;
            currentOrder.value.order_number = `OFF-${Date.now()}`;

            await offlineDb.orders.put(currentOrder.value);
            orders.value.unshift({ ...currentOrder.value });
            
            appStore.incrementPendingOffline();
            appStore.addToSyncQueue({
                type: 'order',
                action: 'create',
                data: currentOrder.value
            });

            return { success: true, data: currentOrder.value, offline: true };
        } catch (err) {
            error.value = 'Failed to save order offline';
            console.error('Save order offline error:', err);
            return { success: false, error: error.value };
        }
    }

    /**
     * Update order status
     */
    async function updateOrderStatus(orderId, status) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/${orderId}/status' to '/orders/${orderId}/status'
            // Also: Your backend uses PUT not PATCH for updates
            const response = await api.put(`/orders/${orderId}`, { status });

            if (response.data.success) {
                const updatedOrder = response.data.data;
                
                const index = orders.value.findIndex(o => o.id === orderId);
                if (index !== -1) {
                    orders.value[index] = updatedOrder;
                }
                
                if (currentOrder.value?.id === orderId) {
                    currentOrder.value = updatedOrder;
                }
                
                return { success: true, data: updatedOrder };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Update order status error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Send order to kitchen
     */
    async function sendToKitchen(orderId = null) {
        const id = orderId || currentOrder.value?.id;
        if (!id) return { success: false, error: 'No order to send' };

        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/${id}/send-kitchen' to '/orders/${id}/send-kitchen'
            const response = await api.post(`/orders/${id}/send-kitchen`);

            if (response.data.success) {
                const updatedOrder = response.data.data;
                
                const index = orders.value.findIndex(o => o.id === id);
                if (index !== -1) {
                    orders.value[index] = updatedOrder;
                }
                
                if (currentOrder.value?.id === id) {
                    currentOrder.value = updatedOrder;
                }
                
                return { success: true, data: updatedOrder };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Send to kitchen error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Void an order item
     */
    async function voidOrderItem(orderId, itemId, reason) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/...' to '/orders/...'
            const response = await api.post(`/orders/${orderId}/items/${itemId}/void`, {
                reason: reason
            });

            if (response.data.success) {
                await fetchOrder(orderId);
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Void order item error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Apply discount to order
     */
    async function applyDiscount(orderId, discountData) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/...' to '/orders/...'
            const response = await api.post(`/orders/${orderId}/discount`, discountData);

            if (response.data.success) {
                await fetchOrder(orderId);
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Apply discount error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Assign customer to order
     */
    async function assignCustomer(orderId, customerId) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/...' to '/orders/...'
            const response = await api.put(`/orders/${orderId}`, {
                customer_id: customerId
            });

            if (response.data.success) {
                await fetchOrder(orderId);
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Assign customer error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Process payment for order
     */
    async function processPayment(orderId, paymentData) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/orders/...' to '/orders/...'
            const response = await api.post(`/orders/${orderId}/pay`, paymentData);

            if (response.data.success) {
                const index = orders.value.findIndex(o => o.id === orderId);
                if (index !== -1) {
                    orders.value[index].status = 'paid';
                }
                
                return { success: true, data: response.data.data };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Process payment error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Get recent invoices
     */
    async function getRecentInvoices(limit = 10) {
        try {
            // ✅ FIXED: Changed from '/api/invoices' to '/invoices'
            const response = await api.get('/invoices', {
                params: { limit, sort: '-created_at' }
            });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Get recent invoices error:', err);
        }
        return [];
    }

    /**
     * Delete order
     */
    async function deleteOrder(orderId) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.delete(`/orders/${orderId}`);

            if (response.data.success) {
                orders.value = orders.value.filter(o => o.id !== orderId);
                
                if (currentOrder.value?.id === orderId) {
                    currentOrder.value = null;
                }
                
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Delete order error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Set current order
     */
    function setCurrentOrder(order) {
        currentOrder.value = order;
    }

    /**
     * Load orders from offline database
     */
    async function loadOrdersFromOffline() {
        try {
            const offlineOrders = await offlineDb.orders.toArray();
            orders.value = offlineOrders;
            return orders.value;
        } catch (err) {
            console.error('Load orders from offline error:', err);
            return [];
        }
    }

    return {
        // State
        orders,
        currentOrder,
        isLoading,
        error,

        // Getters
        activeOrders,
        completedOrders,
        kitchenOrders,
        ordersByTable,
        currentOrderItems,
        currentOrderSubtotal,
        currentOrderItemCount,

        // Actions
        fetchOrders,
        fetchOrder,
        createNewOrder,
        addItemToOrder,
        updateItemQuantity,
        removeItemFromOrder,
        clearCurrentOrder,
        saveOrder,
        saveOrderOffline,
        updateOrderStatus,
        sendToKitchen,
        voidOrderItem,
        applyDiscount,
        assignCustomer,
        processPayment,
        getRecentInvoices,
        deleteOrder,
        setCurrentOrder,
        loadOrdersFromOffline
    };
});