import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useReportStore = defineStore('report', () => {
    const isLoading = ref(false);
    const error = ref(null);

    async function getDailySales(date) {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get('/reports/daily-sales', { params: { date } });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Failed to fetch daily sales:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function getHourlySales(date) {
        isLoading.value = true;
        try {
            const response = await api.get('/reports/hourly-sales', { params: { date } });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch hourly sales:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function getItemsSold(startDate, endDate) {
        isLoading.value = true;
        try {
            const response = await api.get('/reports/items-sold', { 
                params: { start_date: startDate, end_date: endDate } 
            });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch items sold:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function getPaymentMethods(startDate, endDate) {
        isLoading.value = true;
        try {
            const response = await api.get('/reports/payment-methods', { 
                params: { start_date: startDate, end_date: endDate } 
            });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch payment methods:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function getStaffPerformance(startDate, endDate) {
        isLoading.value = true;
        try {
            const response = await api.get('/reports/staff-performance', { 
                params: { start_date: startDate, end_date: endDate } 
            });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch staff performance:', err);
        } finally {
            isLoading.value = false;
        }
    }

    return {
        isLoading,
        error,
        getDailySales,
        getHourlySales,
        getItemsSold,
        getPaymentMethods,
        getStaffPerformance,
    };
});