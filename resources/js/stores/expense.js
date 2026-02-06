import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useExpenseStore = defineStore('expense', () => {
    const expenses = ref([]);
    const categories = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    const totalExpenses = computed(() => 
        expenses.value.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0)
    );

    const expensesByCategory = computed(() => {
        const grouped = {};
        expenses.value.forEach(exp => {
            const catName = exp.category?.name || 'Uncategorized';
            if (!grouped[catName]) {
                grouped[catName] = 0;
            }
            grouped[catName] += parseFloat(exp.amount || 0);
        });
        return grouped;
    });

    async function fetchExpenses(params = {}) {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get('/expenses', { params });
            if (response.data.success) {
                expenses.value = response.data.data;
                return expenses.value;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Failed to fetch expenses:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchCategories() {
        try {
            const response = await api.get('/expenses/categories');
            if (response.data.success) {
                categories.value = response.data.data;
                return categories.value;
            }
        } catch (err) {
            console.error('Failed to fetch expense categories:', err);
        }
    }

    async function createExpense(data) {
        isLoading.value = true;
        try {
            const response = await api.post('/expenses', data);
            if (response.data.success) {
                expenses.value.unshift(response.data.data);
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function updateExpense(id, data) {
        isLoading.value = true;
        try {
            const response = await api.put(`/expenses/${id}`, data);
            if (response.data.success) {
                const index = expenses.value.findIndex(e => e.id === id);
                if (index !== -1) {
                    expenses.value[index] = response.data.data;
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

    async function deleteExpense(id) {
        isLoading.value = true;
        try {
            const response = await api.delete(`/expenses/${id}`);
            if (response.data.success) {
                expenses.value = expenses.value.filter(e => e.id !== id);
                return { success: true };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchSummary(params = {}) {
        try {
            const response = await api.get('/expenses/summary', { params });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch expense summary:', err);
        }
    }

    return {
        expenses,
        categories,
        isLoading,
        error,
        totalExpenses,
        expensesByCategory,
        fetchExpenses,
        fetchCategories,
        createExpense,
        updateExpense,
        deleteExpense,
        fetchSummary,
    };
});