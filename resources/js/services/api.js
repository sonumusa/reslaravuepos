import axios from 'axios';

// ═══════════════════════════════════════════════════════
// AXIOS INSTANCE
// ═══════════════════════════════════════════════════════
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    withCredentials: true,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// ═══════════════════════════════════════════════════════
// REQUEST INTERCEPTOR
// ═══════════════════════════════════════════════════════
api.interceptors.request.use(
    (config) => {
        // Add auth token
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        // Add branch header
        const branchId = localStorage.getItem('branch_id');
        if (branchId) {
            config.headers['X-Branch-Id'] = branchId;
        }
        
        // Add terminal header
        const terminalId = localStorage.getItem('terminal_id');
        if (terminalId) {
            config.headers['X-Terminal-Id'] = terminalId;
        }
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        // Log request in dev mode
        if (import.meta.env.DEV) {
            console.log(`📤 ${config.method?.toUpperCase()} ${config.url}`, config.data || '');
        }
        
        return config;
    },
    (error) => {
        console.error('Request error:', error);
        return Promise.reject(error);
    }
);

// ═══════════════════════════════════════════════════════
// RESPONSE INTERCEPTOR
// ═══════════════════════════════════════════════════════
api.interceptors.response.use(
    (response) => {
        // Log response in dev mode
        if (import.meta.env.DEV) {
            console.log(`📥 ${response.config.method?.toUpperCase()} ${response.config.url}`, response.data);
        }
        
        return response;
    },
    async (error) => {
        const originalRequest = error.config;
        
        // Handle double prefix issue /api/api
        if (error.response && error.response.status === 404 && originalRequest.url.includes('/api/api/')) {
            console.warn('Double API prefix detected, retrying...', originalRequest.url);
            originalRequest.url = originalRequest.url.replace('/api/api/', '/api/');
            return api(originalRequest);
        }

        // Handle network errors
        if (!error.response) {
            console.error('Network error - server unreachable');
            
            // Dispatch offline event
            window.dispatchEvent(new CustomEvent('api:offline'));
            
            return Promise.reject({
                message: 'Network error. Please check your connection.',
                isNetworkError: true,
            });
        }
        
        // Handle 401 Unauthorized
        if (error.response.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            // Try to refresh token
            const token = localStorage.getItem('auth_token');
            if (token) {
                try {
                    const refreshResponse = await axios.post('/api/auth/refresh', {}, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    
                    if (refreshResponse.data.success) {
                        const newToken = refreshResponse.data.data.token;
                        localStorage.setItem('auth_token', newToken);
                        originalRequest.headers.Authorization = `Bearer ${newToken}`;
                        return api(originalRequest);
                    }
                } catch (refreshError) {
                    console.error('Token refresh failed');
                }
            }
            
            // Clear auth and redirect to login
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
            
            return Promise.reject(error);
        }
        
        // Handle 403 Forbidden
        if (error.response.status === 403) {
            window.dispatchEvent(new CustomEvent('api:forbidden', {
                detail: { message: error.response.data?.message || 'Access denied' }
            }));
        }
        
        // Handle 422 Validation errors
        if (error.response.status === 422) {
            const errors = error.response.data?.errors || {};
            return Promise.reject({
                message: error.response.data?.message || 'Validation failed',
                errors,
                isValidationError: true,
            });
        }
        
        // Handle 429 Too Many Requests
        if (error.response.status === 429) {
            return Promise.reject({
                message: 'Too many requests. Please wait a moment.',
                isRateLimited: true,
            });
        }
        
        // Handle 500+ Server errors
        if (error.response.status >= 500) {
            return Promise.reject({
                message: 'Server error. Please try again later.',
                isServerError: true,
            });
        }
        
        // Default error handling
        return Promise.reject({
            message: error.response.data?.message || 'An error occurred',
            status: error.response.status,
        });
    }
);

// ═══════════════════════════════════════════════════════
// API METHODS
// ═══════════════════════════════════════════════════════
export default api;

// ═══════════════════════════════════════════════════════
// AUTH API
// ═══════════════════════════════════════════════════════
export const authApi = {
    login: (email, password) => api.post('/auth/login', { email, password }),
    pinLogin: (pin, branchId) => api.post('/auth/login-pin', { pin, branch_id: branchId }),
    logout: () => api.post('/auth/logout'),
    logoutAll: () => api.post('/auth/logout-all'),
    me: () => api.get('/auth/me'),
    user: () => api.get('/auth/user'),
    updateProfile: (data) => api.put('/auth/profile', data),
    changePassword: (data) => api.post('/auth/change-password', data),
    changePin: (data) => api.post('/auth/change-pin', data),
    refreshToken: () => api.post('/auth/refresh'),
    verifyPin: (pin) => api.post('/auth/verify-pin', { pin }),
};

// ═══════════════════════════════════════════════════════
// MENU API
// ═══════════════════════════════════════════════════════
export const menuApi = {
    // Categories
    getCategories: (params = {}) => api.get('/categories', { params }),
    getCategory: (id) => api.get(`/categories/${id}`),
    createCategory: (data) => api.post('/categories', data),
    updateCategory: (id, data) => api.put(`/categories/${id}`, data),
    deleteCategory: (id) => api.delete(`/categories/${id}`),
    reorderCategories: (categories) => api.post('/categories/reorder', { categories }),
    
    // Menu Items
    getMenuItems: (params = {}) => api.get('/menu-items', { params }),
    getMenuItem: (id) => api.get(`/menu-items/${id}`),
    createMenuItem: (data) => api.post('/menu-items', data),
    updateMenuItem: (id, data) => api.put(`/menu-items/${id}`, data),
    deleteMenuItem: (id) => api.delete(`/menu-items/${id}`),
    toggleAvailability: (id) => api.post(`/menu-items/${id}/toggle-availability`),
    findByBarcode: (barcode) => api.post('/menu-items/barcode', { barcode }),
    bulkUpdatePrices: (items) => api.post('/menu-items/bulk-update-prices', { items }),
    
    // Modifiers
    getModifiers: (params = {}) => api.get('/modifiers', { params }),
    getModifierGroups: () => api.get('/modifiers/groups'),
    createModifier: (data) => api.post('/modifiers', data),
    updateModifier: (id, data) => api.put(`/modifiers/${id}`, data),
    deleteModifier: (id) => api.delete(`/modifiers/${id}`),
    
    // Daily Specials
    getDailySpecials: (params = {}) => api.get('/daily-specials', { params }),
    createDailySpecial: (data) => api.post('/daily-specials', data),
    updateDailySpecial: (id, data) => api.put(`/daily-specials/${id}`, data),
    deleteDailySpecial: (id) => api.delete(`/daily-specials/${id}`),
};

// ═══════════════════════════════════════════════════════
// TABLE API
// ═══════════════════════════════════════════════════════
export const tableApi = {
    getTables: (params = {}) => api.get('/tables', { params }),
    getFloors: () => api.get('/tables/floors'),
    getTable: (id) => api.get(`/tables/${id}`),
    createTable: (data) => api.post('/tables', data),
    updateTable: (id, data) => api.put(`/tables/${id}`, data),
    updateTableStatus: (id, status) => api.put(`/tables/${id}/status`, { status }),
    deleteTable: (id) => api.delete(`/tables/${id}`),
    transferOrder: (data) => api.post('/tables/transfer', data),
    mergeTables: (data) => api.post('/tables/merge', data),
};

// ═══════════════════════════════════════════════════════
// ORDER API
// ═══════════════════════════════════════════════════════
export const orderApi = {
    getOrders: (params = {}) => api.get('/orders', { params }),
    getCompletedOrders: () => api.get('/orders/completed'),
    getHeldOrders: () => api.get('/orders/held'),
    getOrdersByTable: (tableId) => api.get(`/orders/table/${tableId}`),
    getOrder: (id) => api.get(`/orders/${id}`),
    createOrder: (data) => api.post('/orders', data),
    updateOrder: (id, data) => api.put(`/orders/${id}`, data),
    deleteOrder: (id) => api.delete(`/orders/${id}`),
    
    // Order items
    addItem: (orderId, data) => api.post(`/orders/${orderId}/items`, data),
    updateItem: (orderId, itemId, data) => api.put(`/orders/${orderId}/items/${itemId}`, data),
    removeItem: (orderId, itemId) => api.delete(`/orders/${orderId}/items/${itemId}`),
    
    // Order actions
    holdOrder: (id) => api.post(`/orders/${id}/hold`),
    resumeOrder: (id) => api.post(`/orders/${id}/resume`),
    sendToKitchen: (id) => api.post(`/orders/${id}/send-kitchen`),
    markReady: (id) => api.post(`/orders/${id}/ready`),
    markServed: (id) => api.post(`/orders/${id}/served`),
    completeOrder: (id) => api.post(`/orders/${id}/complete`),
    cancelOrder: (id, reason) => api.post(`/orders/${id}/cancel`, { reason }),
    voidOrder: (id, reason, managerPin) => api.post(`/orders/${id}/void`, { reason, manager_pin: managerPin }),
    
    // Payment
    payOrder: (id, data) => api.post(`/orders/${id}/pay`, data),
};

// ═══════════════════════════════════════════════════════
// INVOICE API
// ═══════════════════════════════════════════════════════
export const invoiceApi = {
    getInvoices: (params = {}) => api.get('/invoices', { params }),
    getPraPending: () => api.get('/invoices/pra-pending'),
    getInvoice: (id) => api.get(`/invoices/${id}`),
    getReceipt: (id) => api.get(`/invoices/${id}/receipt`),
    createInvoice: (data) => api.post('/invoices', data),
    voidInvoice: (id, reason, managerPin) => api.post(`/invoices/${id}/void`, { reason, manager_pin: managerPin }),
    refundInvoice: (id, data) => api.post(`/invoices/${id}/refund`, data),
};

// ═══════════════════════════════════════════════════════
// PAYMENT API
// ═══════════════════════════════════════════════════════
export const paymentApi = {
    getPayments: (params = {}) => api.get('/payments', { params }),
    getPayment: (id) => api.get(`/payments/${id}`),
    processPayment: (data) => api.post('/payments', data),
    splitPayment: (data) => api.post('/payments/split', data),
    refundPayment: (id, data) => api.post(`/payments/${id}/refund`, data),
};

// ═══════════════════════════════════════════════════════
// CUSTOMER API
// ═══════════════════════════════════════════════════════
export const customerApi = {
    getCustomers: (params = {}) => api.get('/customers', { params }),
    searchCustomers: (phone) => api.get('/customers/search', { params: { phone } }),
    getWalkin: () => api.get('/customers/walkin'),
    getCustomer: (id) => api.get(`/customers/${id}`),
    createCustomer: (data) => api.post('/customers', data),
    updateCustomer: (id, data) => api.put(`/customers/${id}`, data),
    getCustomerOrders: (id) => api.get(`/customers/${id}/orders`),
    redeemPoints: (id, data) => api.post(`/customers/${id}/redeem-points`, data),
};

// ═══════════════════════════════════════════════════════
// POS SESSION API
// ═══════════════════════════════════════════════════════
export const posSessionApi = {
    getSessions: (params = {}) => api.get('/pos-sessions', { params }),
    getCurrentSession: () => api.get('/pos-sessions/current'),
    getSession: (id) => api.get(`/pos-sessions/${id}`),
    getSessionSummary: (id) => api.get(`/pos-sessions/${id}/summary`),
    openSession: (data) => api.post('/pos-sessions/open', data),
    closeSession: (data) => api.post('/pos-sessions/close', data),
    suspendSession: (reason) => api.post('/pos-sessions/suspend', { reason }),
    resumeSession: () => api.post('/pos-sessions/resume'),
    cashDrop: (data) => api.post('/pos-sessions/cash-drop', data),
    xReport: () => api.get('/pos-sessions/x-report'),
    zReport: (date) => api.get('/pos-sessions/z-report', { params: { date } }),
};

// ═══════════════════════════════════════════════════════
// SYNC API
// ═══════════════════════════════════════════════════════
export const syncApi = {
    ping: () => api.get('/sync/ping'),
    getStatus: () => api.get('/sync/status'),
    download: (params = {}) => api.get('/sync/download', { params }),
    upload: (data) => api.post('/sync/upload', data),
    resolveConflicts: (conflicts) => api.post('/sync/resolve-conflicts', { conflicts }),
};

// ═══════════════════════════════════════════════════════
// PRA API
// ═══════════════════════════════════════════════════════
export const praApi = {
    getStatus: () => api.get('/pra/status'),
    getPending: () => api.get('/pra/pending'),
    getLogs: (params = {}) => api.get('/pra/logs', { params }),
    testConnection: () => api.post('/pra/test-connection'),
    submit: (invoiceId) => api.post(`/pra/submit/${invoiceId}`),
    retry: (invoiceId) => api.post(`/pra/retry/${invoiceId}`),
    retryAll: () => api.post('/pra/retry-all'),
    updateSettings: (data) => api.put('/pra/settings', data),
};

// ═══════════════════════════════════════════════════════
// KDS API
// ═══════════════════════════════════════════════════════
export const kdsApi = {
    getOrders: () => api.get('/kds'),
    getStats: () => api.get('/kds/stats'),
    acknowledgeOrder: (orderId) => api.post(`/kds/${orderId}/acknowledge`),
    startPreparing: (orderId) => api.post(`/kds/${orderId}/start`),
    itemReady: (orderId, itemId) => api.post(`/kds/${orderId}/item/${itemId}/ready`),
    bumpOrder: (orderId) => api.post(`/kds/${orderId}/bump`),
    recallOrder: (orderId) => api.post(`/kds/${orderId}/recall`),
};

// ═══════════════════════════════════════════════════════
// BRANCH API
// ═══════════════════════════════════════════════════════
export const branchApi = {
    getBranches: (params = {}) => api.get('/branches', { params }),
    getBranch: (id) => api.get(`/branches/${id}`),
    createBranch: (data) => api.post('/branches', data),
    updateBranch: (id, data) => api.put(`/branches/${id}`, data),
    deleteBranch: (id) => api.delete(`/branches/${id}`),
    getBranchStats: (id) => api.get(`/branches/${id}/stats`),
};

// ═══════════════════════════════════════════════════════
// REPORTS API
// ═══════════════════════════════════════════════════════
export const reportsApi = {
    getDailySales: (date) => api.get('/reports/daily-sales', { params: { date } }),
    getHourlySales: (date) => api.get('/reports/hourly-sales', { params: { date } }),
    getItemsSold: (params) => api.get('/reports/items-sold', { params }),
    getPaymentMethods: (params) => api.get('/reports/payment-methods', { params }),
    getCashierSummary: (params) => api.get('/reports/cashier-summary', { params }),
    getWaiterSummary: (params) => api.get('/reports/waiter-summary', { params }),
    getTaxSummary: (params) => api.get('/reports/tax-summary', { params }),
    getPraStatus: (params) => api.get('/reports/pra-status', { params }),
};
