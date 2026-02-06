import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import { useAuthStore } from './auth';
import { useOfflineDb } from '@/services/offline-db';

export const useTableStore = defineStore('table', () => {
    const offlineDb = useOfflineDb();

    // State
    const tables = ref([]);
    const floors = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    // Getters
    const activeTables = computed(() => 
        tables.value.filter(t => t.is_active)
    );

    const availableTables = computed(() => 
        tables.value.filter(t => t.is_active && t.status === 'available')
    );

    const occupiedTables = computed(() => 
        tables.value.filter(t => t.status === 'occupied')
    );

    const reservedTables = computed(() => 
        tables.value.filter(t => t.status === 'reserved')
    );

    const tablesByFloor = computed(() => {
        const grouped = {};
        floors.value.forEach(floor => {
            grouped[floor] = tables.value.filter(t => t.floor === floor && t.is_active);
        });
        return grouped;
    });

    const getTableById = computed(() => (id) => 
        tables.value.find(t => t.id === id)
    );

    const getTableStatus = computed(() => (id) => {
        const table = tables.value.find(t => t.id === id);
        return table?.status || 'unknown';
    });

    // Actions
    async function fetchTables(forceRefresh = false) {
        const authStore = useAuthStore();

        if (!forceRefresh && tables.value.length > 0) {
            return tables.value;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.get('/api/tables', {
                params: { branch_id: authStore.branchId }
            });

            if (response.data.success) {
                tables.value = response.data.data;
                
                // Extract unique floors
                floors.value = [...new Set(tables.value.map(t => t.floor))].sort();
                
                // Save to offline DB
                await offlineDb.posTables.clear();
                await offlineDb.posTables.bulkPut(tables.value);
                
                return tables.value;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            
            // Load from offline
            await loadFromOffline();
            
            return tables.value;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateTableStatus(tableId, status) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.patch(`/api/tables/${tableId}/status`, { status });

            if (response.data.success) {
                const index = tables.value.findIndex(t => t.id === tableId);
                if (index !== -1) {
                    tables.value[index] = response.data.data;
                }
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function setTableOccupied(tableId) {
        return await updateTableStatus(tableId, 'occupied');
    }

    async function setTableAvailable(tableId) {
        return await updateTableStatus(tableId, 'available');
    }

    async function setTableReserved(tableId) {
        return await updateTableStatus(tableId, 'reserved');
    }

    async function transferTable(orderId, fromTableId, toTableId, reason = '') {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post('/api/table-transfers', {
                order_id: orderId,
                from_table_id: fromTableId,
                to_table_id: toTableId,
                reason: reason
            });

            if (response.data.success) {
                // Refresh tables to get updated statuses
                await fetchTables(true);
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function createTable(data) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post('/api/tables', data);
            if (response.data.success) {
                tables.value.push(response.data.data);
                return { success: true, data: response.data.data };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function updateTable(id, data) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.put(`/api/tables/${id}`, data);
            if (response.data.success) {
                const index = tables.value.findIndex(t => t.id === id);
                if (index !== -1) {
                    tables.value[index] = response.data.data;
                }
                return { success: true, data: response.data.data };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteTable(id) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.delete(`/api/tables/${id}`);
            if (response.data.success) {
                tables.value = tables.value.filter(t => t.id !== id);
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    function updateLocalTableStatus(tableId, status) {
        const index = tables.value.findIndex(t => t.id === tableId);
        if (index !== -1) {
            tables.value[index].status = status;
        }
    }

    async function loadFromOffline() {
        try {
            const offlineData = await offlineDb.posTables.toArray();
            if (offlineData.length > 0) {
                tables.value = offlineData;
                floors.value = [...new Set(offlineData.map(t => t.floor))].sort();
                return true;
            }
            return false;
        } catch (err) {
            console.error('Load tables from offline error:', err);
            return false;
        }
    }

    return {
        // State
        tables,
        floors,
        isLoading,
        error,

        // Getters
        activeTables,
        availableTables,
        occupiedTables,
        reservedTables,
        tablesByFloor,
        getTableById,
        getTableStatus,

        // Actions
        fetchTables,
        updateTableStatus,
        setTableOccupied,
        setTableAvailable,
        setTableReserved,
        transferTable,
        createTable,
        updateTable,
        deleteTable,
        updateLocalTableStatus,
        loadFromOffline
    };
});
