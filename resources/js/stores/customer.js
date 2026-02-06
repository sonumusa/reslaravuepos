import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import { useAuthStore } from './auth';
import { useOfflineDb } from '@/services/offline-db';

export const useCustomerStore = defineStore('customer', () => {
    const offlineDb = useOfflineDb();

    // State
    const customers = ref([]);
    const currentCustomer = ref(null);
    const recentCustomers = ref([]);
    const isLoading = ref(false);
    const error = ref(null);
    const searchResults = ref([]);

    // Getters
    const activeCustomers = computed(() => 
        customers.value.filter(c => !c.deleted_at)
    );

    const loyalCustomers = computed(() => 
        customers.value.filter(c => c.loyalty_points >= 100)
    );

    const getCustomerById = computed(() => (id) => 
        customers.value.find(c => c.id === id)
    );

    // ═══════════════════════════════════════════════════════
    // ACTIONS - All API paths fixed
    // ═══════════════════════════════════════════════════════

    /**
     * Fetch all customers
     */
    async function fetchCustomers(params = {}) {
        const authStore = useAuthStore();
        
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers' to '/customers'
            const response = await api.get('/customers', {
                params: {
                    branch_id: authStore.branchId,
                    ...params
                }
            });

            if (response.data.success) {
                customers.value = response.data.data;
                
                await offlineDb.customers.clear();
                await offlineDb.customers.bulkPut(customers.value);
                
                return customers.value;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Fetch customers error:', err);
            
            // Load from offline
            await loadFromOffline();
            return customers.value;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Search customers
     */
    async function searchCustomers(query) {
        if (!query || query.length < 2) {
            searchResults.value = [];
            return [];
        }

        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/search' to '/customers/search'
            const response = await api.get('/customers/search', {
                params: { q: query, phone: query }
            });

            if (response.data.success) {
                searchResults.value = response.data.data;
                return searchResults.value;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Search customers error:', err);
            
            // Search offline
            const lowerQuery = query.toLowerCase();
            searchResults.value = customers.value.filter(c => 
                c.name.toLowerCase().includes(lowerQuery) || 
                c.phone?.includes(query) || 
                c.email?.toLowerCase().includes(lowerQuery)
            );
            return searchResults.value;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Fetch single customer
     */
    async function fetchCustomer(id) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/${id}' to '/customers/${id}'
            const response = await api.get(`/customers/${id}`);
            
            if (response.data.success) {
                currentCustomer.value = response.data.data;
                return currentCustomer.value;
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Fetch customer error:', err);
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Create new customer
     */
    async function createCustomer(data) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers' to '/customers'
            const response = await api.post('/customers', data);
            
            if (response.data.success) {
                const newCustomer = response.data.data;
                customers.value.unshift(newCustomer);
                recentCustomers.value.unshift(newCustomer);
                return { success: true, data: newCustomer };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Create customer error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Update customer
     */
    async function updateCustomer(id, data) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/${id}' to '/customers/${id}'
            const response = await api.put(`/customers/${id}`, data);
            
            if (response.data.success) {
                const updatedCustomer = response.data.data;
                const index = customers.value.findIndex(c => c.id === id);
                if (index !== -1) {
                    customers.value[index] = updatedCustomer;
                }
                if (currentCustomer.value?.id === id) {
                    currentCustomer.value = updatedCustomer;
                }
                return { success: true, data: updatedCustomer };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Update customer error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Delete customer
     */
    async function deleteCustomer(id) {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/${id}' to '/customers/${id}'
            const response = await api.delete(`/customers/${id}`);
            
            if (response.data.success) {
                customers.value = customers.value.filter(c => c.id !== id);
                if (currentCustomer.value?.id === id) {
                    currentCustomer.value = null;
                }
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Delete customer error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Add loyalty points
     */
    async function addLoyaltyPoints(customerId, points, invoiceId = null, description = '') {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/...' to '/customers/...'
            const response = await api.post(`/customers/${customerId}/loyalty`, {
                type: 'earn',
                points: points,
                invoice_id: invoiceId,
                description: description
            });

            if (response.data.success) {
                const index = customers.value.findIndex(c => c.id === customerId);
                if (index !== -1) {
                    customers.value[index].loyalty_points += points;
                }
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Add loyalty points error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Redeem loyalty points
     */
    async function redeemLoyaltyPoints(customerId, points, invoiceId = null, description = '') {
        isLoading.value = true;
        error.value = null;

        try {
            // ✅ FIXED: Changed from '/api/customers/...' to '/customers/...'
            const response = await api.post(`/customers/${customerId}/redeem-points`, {
                points: points,
                invoice_id: invoiceId,
                description: description
            });

            if (response.data.success) {
                const index = customers.value.findIndex(c => c.id === customerId);
                if (index !== -1) {
                    customers.value[index].loyalty_points -= points;
                }
                return { success: true };
            }
            throw new Error(response.data.message);
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Redeem loyalty points error:', err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Set current customer
     */
    function setCurrentCustomer(customer) {
        currentCustomer.value = customer;
        
        // Add to recent if not already there
        if (customer && !recentCustomers.value.find(c => c.id === customer.id)) {
            recentCustomers.value.unshift(customer);
            if (recentCustomers.value.length > 10) {
                recentCustomers.value = recentCustomers.value.slice(0, 10);
            }
        }
    }

    /**
     * Clear current customer
     */
    function clearCurrentCustomer() {
        currentCustomer.value = null;
    }

    /**
     * Load from offline storage
     */
    async function loadFromOffline() {
        try {
            const offlineData = await offlineDb.customers.toArray();
            customers.value = offlineData;
            return customers.value;
        } catch (err) {
            console.error('Load customers from offline error:', err);
            return [];
        }
    }

    return {
        // State
        customers,
        currentCustomer,
        recentCustomers,
        isLoading,
        error,
        searchResults,
        
        // Getters
        activeCustomers,
        loyalCustomers,
        getCustomerById,
        
        // Actions
        fetchCustomers,
        searchCustomers,
        fetchCustomer,
        createCustomer,
        updateCustomer,
        deleteCustomer,
        addLoyaltyPoints,
        redeemLoyaltyPoints,
        setCurrentCustomer,
        clearCurrentCustomer,
        loadFromOffline
    };
}, {
    persist: {
        key: 'customer',
        paths: ['recentCustomers'],
        storage: localStorage
    }
});