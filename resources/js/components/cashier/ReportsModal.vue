<template>
    <Modal :model-value="true" title="Session Reports" size="lg" @close="$emit('close')">
        <div class="space-y-6">
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-xl text-center">
                    <p class="text-sm text-blue-600 mb-1">Total Sales</p>
                    <p class="text-2xl font-bold text-blue-800">Rs. {{ (session?.total_sales || 0).toLocaleString() }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-xl text-center">
                    <p class="text-sm text-green-600 mb-1">Cash In Drawer</p>
                    <p class="text-2xl font-bold text-green-800">Rs. {{ ((session?.opening_cash || 0) + (session?.total_cash_sales || 0)).toLocaleString() }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-xl text-center">
                    <p class="text-sm text-purple-600 mb-1">Total Orders</p>
                    <p class="text-2xl font-bold text-purple-800">{{ session?.total_orders || 0 }}</p>
                </div>
            </div>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-left">Payment Method</th>
                            <th class="p-3 text-right">Count</th>
                            <th class="p-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="p-3">Cash</td>
                            <td class="p-3 text-right">-</td>
                            <td class="p-3 text-right font-medium">Rs. {{ (session?.total_cash_sales || 0).toLocaleString() }}</td>
                        </tr>
                        <tr>
                            <td class="p-3">Credit Card</td>
                            <td class="p-3 text-right">-</td>
                            <td class="p-3 text-right font-medium">Rs. {{ (session?.total_card_sales || 0).toLocaleString() }}</td>
                        </tr>
                        <tr class="bg-gray-50 font-bold">
                            <td class="p-3">Total</td>
                            <td class="p-3 text-right">{{ session?.total_orders || 0 }}</td>
                            <td class="p-3 text-right">Rs. {{ (session?.total_sales || 0).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3">
                <button class="btn-secondary">
                    <i class="fas fa-print mr-2"></i> Print X-Report
                </button>
                <button class="btn-secondary">
                    <i class="fas fa-print mr-2"></i> Print Z-Report
                </button>
            </div>
        </div>
        
        <template #footer>
            <button @click="$emit('close')" class="btn-primary w-full">Close</button>
        </template>
    </Modal>
</template>

<script setup>
import Modal from '@/components/common/Modal.vue';

defineProps({
    session: Object
});

defineEmits(['close']);
</script>