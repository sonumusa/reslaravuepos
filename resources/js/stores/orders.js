import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import { useOfflineDb } from '@/services/offline-db';

export const useOrdersStore = defineStore('orders', () => {
    // ═══════════════════════════════════════════════════════
    // STATE
    // ═══════════════════════════════════════════════════════
    const orders = ref([]);
    const currentOrder = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    // ═══════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════
    const pendingOrders = computed(() => 
        orders.value.filter(o => ['open', 'hold'].includes(o.status))
    );

    const kitchenOrders = computed(() => 
        orders.value.filter(o => ['sent_to_kitchen', 'preparing'].includes(o.status))
    );

    const readyOrders = computed(() => 
        orders.value.filter(o => o.status === 'ready')
    );

    const completedOrders = computed(() => 
        orders.value.filter(o => o.status === 'completed')
    );

    const getOrderById = computed(() => (id) => 
        orders.value.find(o => o.id === id || o.uuid === id)
    );

    const getOrdersByTable = computed(() => (tableId) => 
        orders.value.filter(o => o.table_id === tableId && !['paid', 'cancelled', 'void'].includes(o.status))
    );

    // ═══════════════════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════════════════
    
    /**
     * Fetch orders
     */
    async function fetchOrders(filters = {}) {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await api.get('/orders', { params: filters });
            orders.value = response.data.data || [];
            return orders.value;
        } catch (e) {
            error.value = e.message;
            // Try loading from offline
            await loadOrdersOffline();
            throw e;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Create order
     */
    async function createOrder(orderData) {
        isLoading.value = true;
        
        try {
            if (navigator.onLine) {
                const response = await api.post('/orders', orderData);
                if (response.data.success) {
                    const newOrder = response.data.data;
                    orders.value.push(newOrder);
                    return { success: true, order: newOrder };
                }
            }
            
            // Store offline
            orderData.created_offline = true;
            orderData.uuid = orderData.uuid || generateUUID();
            await storeOrderOffline(orderData);
            orders.value.push(orderData);
            
            return { success: true, order: orderData, offline: true };
        } catch (e) {
            error.value = e.message;
            return { success: false, error: e.message };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Update order
     */
    async function updateOrder(orderId, data) {
        try {
            if (navigator.onLine) {
                const response = await api.put(`/orders/${orderId}`, data);
                if (response.data.success) {
                    const index = orders.value.findIndex(o => o.id === orderId);
                    if (index >= 0) {
                        orders.value[index] = { ...orders.value[index], ...response.data.data };
                    }
                    return { success: true };
                }
            }
            
            // Update locally
            const index = orders.value.findIndex(o => o.id === orderId || o.uuid === orderId);
            if (index >= 0) {
                orders.value[index] = { ...orders.value[index], ...data };
                await storeOrderOffline(orders.value[index]);
            }
            
            return { success: true, offline: true };
        } catch (e) {
            return { success: false, error: e.message };
        }
    }

    /**
     * Add item to order
     */
    async function addItemToOrder(orderId, itemData) {
        try {
            if (navigator.onLine) {
                const response = await api.post(`/orders/${orderId}/items`, itemData);
                return { success: response.data.success, item: response.data.data };
            }
            
            // Add locally
            const order = orders.value.find(o => o.id === orderId || o.uuid === orderId);
            if (order) {
                order.items = order.items || [];
                order.items.push({ ...itemData, id: Date.now() });
                await storeOrderOffline(order);
            }
            
            return { success: true, offline: true };
        } catch (e) {
            return { success: false, error: e.message };
        }
    }

    /**
     * Remove item from order
     */
    async function removeItemFromOrder(orderId, itemId) {
        try {
            if (navigator.onLine) {
                await api.delete(`/orders/${orderId}/items/${itemId}`);
            }
            
            // Remove locally
            const order = orders.value.find(o => o.id === orderId || o.uuid === orderId);
            if (order && order.items) {
                order.items = order.items.filter(i => i.id !== itemId);
                await storeOrderOffline(order);
            }
            
            return { success: true };
        } catch (e) {
            return { success: false, error: e.message };
        }
    }

    /**
     * Store order offline
     */
    async function storeOrderOffline(order) {
        try {
            const db = useOfflineDb();
            await db.orders.put({
                ...order,
                synced: false,
                updated_at: new Date().toISOString(),
            });
        } catch (e) {
            console.error('Failed to store order offline:', e);
        }
    }

    /**
     * Load orders from offline storage
     */
    async function loadOrdersOffline() {
        try {
            const db = useOfflineDb();
            const offlineOrders = await db.orders.toArray();
            
            // Merge with existing, preferring offline versions
            offlineOrders.forEach(offlineOrder => {
                const index = orders.value.findIndex(o => o.uuid === offlineOrder.uuid);
                if (index >= 0) {
                    orders.value[index] = offlineOrder;
                } else {
                    orders.value.push(offlineOrder);
                }
            });
        } catch (e) {
            console.error('Failed to load orders offline:', e);
        }
    }

    /**
     * Get unsynced orders
     */
    async function getUnsyncedOrders() {
        try {
            const db = useOfflineDb();
            return await db.orders.where('synced').equals(false).toArray();
        } catch (e) {
            console.error('Failed to get unsynced orders:', e);
            return [];
        }
    }

    /**
     * Generate UUID
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Apply discount
     */
    async function applyDiscount(orderId, discountData) {
        try {
            if (navigator.onLine) {
                await api.post(`/orders/${orderId}/discount`, discountData);
            }
            
            // Update locally
            const order = orders.value.find(o => o.id === orderId || o.uuid === orderId);
            if (order) {
                order.discount = discountData;
                await storeOrderOffline(order);
            }
            
            return { success: true };
        } catch (e) {
            return { success: false, error: e.message };
        }
    }

    /**
     * Remove discount
     */
    function removeDiscount() {
        if (!currentOrder.value) return;
        currentOrder.value.discount = null;
    }

    /**
     * Process Payment
     */
    async function processPayment(orderId, paymentData) {
        try {
            // 1. Create Invoice
            const invoiceResponse = await api.post('/invoices', { order_id: orderId });
            const invoice = invoiceResponse.data.data;

            // 2. Process Payment
            await api.post('/payments', {
                invoice_id: invoice.id,
                ...paymentData
            });

            return { success: true, invoice };
        } catch (e) {
            throw e;
        }
    }

    /**
     * Void Order Item
     */
    async function voidOrderItem(orderId, itemId, reason) {
        try {
            await api.post(`/orders/${orderId}/items/${itemId}/void`, { reason });
            return { success: true };
        } catch (e) {
            // Fallback to local remove if offline/dev
            return removeItemFromOrder(orderId, itemId);
        }
    }

    /**
     * Assign Customer
     */
    async function assignCustomer(orderId, customerId) {
        try {
            await api.post(`/orders/${orderId}/customer`, { customer_id: customerId });
            return { success: true };
        } catch (e) {
            console.error(e);
            return { success: false, error: e.message };
        }
    }

    /**
     * Get Recent Invoices
     */
    async function getRecentInvoices(limit = 5) {
        try {
            const response = await api.get('/invoices', { params: { limit } });
            return response.data.data || [];
        } catch (e) {
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════
    // RETURN
    // ═══════════════════════════════════════════════════════
    return {
        // State
        orders,
        currentOrder,
        isLoading,
        error,
        
        // Getters
        pendingOrders,
        kitchenOrders,
        readyOrders,
        completedOrders,
        getOrderById,
        getOrdersByTable,
        
        // Actions
        fetchOrders,
        createOrder,
        updateOrder,
        addItemToOrder,
        removeItemFromOrder,
        storeOrderOffline,
        loadOrdersOffline,
        getUnsyncedOrders,
        applyDiscount,
        removeDiscount,
        processPayment,
        voidOrderItem,
        assignCustomer,
        getRecentInvoices,
    };
});
