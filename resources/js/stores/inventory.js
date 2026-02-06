import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useInventoryStore = defineStore('inventory', () => {
    const items = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    const lowStockItems = computed(() => 
        items.value.filter(i => i.stock_quantity > 0 && i.stock_quantity <= i.min_stock_level)
    );

    const outOfStockItems = computed(() => 
        items.value.filter(i => i.stock_quantity === 0)
    );

    const totalValue = computed(() => 
        items.value.reduce((sum, i) => sum + (i.stock_quantity * (i.cost_price || 0)), 0)
    );

    async function fetchInventory() {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get('/inventory');
            if (response.data.success) {
                items.value = response.data.data;
                return items.value;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Failed to fetch inventory:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function createItem(data) {
        isLoading.value = true;
        try {
            const response = await api.post('/inventory', data);
            if (response.data.success) {
                items.value.unshift(response.data.data);
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function updateItem(id, data) {
        isLoading.value = true;
        try {
            const response = await api.put(`/inventory/${id}`, data);
            if (response.data.success) {
                const index = items.value.findIndex(i => i.id === id);
                if (index !== -1) {
                    items.value[index] = response.data.data;
                }
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteItem(id) {
        isLoading.value = true;
        try {
            const response = await api.delete(`/inventory/${id}`);
            if (response.data.success) {
                items.value = items.value.filter(i => i.id !== id);
                return { success: true };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function adjustStock(id, quantity, type, reason) {
        isLoading.value = true;
        try {
            const response = await api.post(`/inventory/${id}/adjust`, { quantity, type, reason });
            if (response.data.success) {
                const index = items.value.findIndex(i => i.id === id);
                if (index !== -1) {
                    items.value[index] = response.data.data;
                }
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    return {
        items,
        isLoading,
        error,
        lowStockItems,
        outOfStockItems,
        totalValue,
        fetchInventory,
        createItem,
        updateItem,
        deleteItem,
        adjustStock,
    };
});