<template>
    <Modal :model-value="true" title="Select Customer" size="md" @close="$emit('close')">
        <div class="space-y-4">
            <SearchInput 
                v-model="searchQuery" 
                placeholder="Search by name or phone..." 
                class="w-full"
                @search="searchCustomers"
            />
            
            <div class="h-64 overflow-y-auto border rounded-lg">
                <div v-if="loading" class="p-4 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Searching...
                </div>
                
                <div v-else-if="customers.length === 0" class="p-4 text-center text-gray-500">
                    No customers found.
                </div>
                
                <div v-else class="divide-y">
                    <div 
                        v-for="customer in customers" 
                        :key="customer.id"
                        @click="selectCustomer(customer)"
                        class="p-3 hover:bg-blue-50 cursor-pointer transition-colors"
                    >
                        <div class="flex justify-between">
                            <span class="font-bold">{{ customer.name }}</span>
                            <span class="text-sm text-gray-500">{{ customer.phone }}</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Points: {{ customer.loyalty_points || 0 }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pt-2 border-t flex justify-between items-center">
                <span class="text-sm text-gray-500">Can't find customer?</span>
                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    + Add New Customer
                </button>
            </div>
        </div>
        
        <template #footer>
            <button @click="$emit('close')" class="btn-secondary w-full">Cancel</button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Modal from '@/components/common/Modal.vue';
import SearchInput from '@/components/common/SearchInput.vue';
import api from '@/services/api';

const emit = defineEmits(['close', 'select']);

const searchQuery = ref('');
const customers = ref([]);
const loading = ref(false);

async function searchCustomers() {
    if (!searchQuery.value) return;
    
    loading.value = true;
    try {
        // Mock API call or real one if endpoint exists
        // const response = await api.get('/customers', { params: { search: searchQuery.value } });
        // customers.value = response.data.data;
        
        // Mock data for now
        await new Promise(r => setTimeout(r, 500));
        customers.value = [
            { id: 1, name: 'John Doe', phone: '0300-1234567', loyalty_points: 150 },
            { id: 2, name: 'Jane Smith', phone: '0321-9876543', loyalty_points: 50 },
        ].filter(c => c.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function selectCustomer(customer) {
    emit('select', customer);
}

// Debounce search
let timeout;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(searchCustomers, 300);
});
</script>