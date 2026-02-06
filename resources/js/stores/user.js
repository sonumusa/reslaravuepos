import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useUserStore = defineStore('user', () => {
    const users = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    const staffByRole = computed(() => {
        const grouped = {};
        users.value.forEach(user => {
            if (!grouped[user.role]) {
                grouped[user.role] = [];
            }
            grouped[user.role].push(user);
        });
        return grouped;
    });

    const activeUsers = computed(() => 
        users.value.filter(u => !u.deleted_at)
    );

    async function fetchUsers(params = {}) {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get('/users', { params });
            if (response.data.success) {
                users.value = response.data.data;
                return users.value;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            console.error('Failed to fetch users:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function createUser(data) {
        isLoading.value = true;
        try {
            const response = await api.post('/users', data);
            if (response.data.success) {
                users.value.unshift(response.data.data);
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    async function updateUser(id, data) {
        isLoading.value = true;
        try {
            const response = await api.put(`/users/${id}`, data);
            if (response.data.success) {
                const index = users.value.findIndex(u => u.id === id);
                if (index !== -1) {
                    users.value[index] = response.data.data;
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

    async function deleteUser(id) {
        isLoading.value = true;
        try {
            const response = await api.delete(`/users/${id}`);
            if (response.data.success) {
                users.value = users.value.filter(u => u.id !== id);
                return { success: true };
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message;
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    }

    return {
        users,
        isLoading,
        error,
        staffByRole,
        activeUsers,
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
    };
});