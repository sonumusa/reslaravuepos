<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
      <button 
        @click="openCreateModal"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        <PlusIcon class="w-5 h-5" />
        <span>Add Item</span>
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-gray-500 text-sm">Total Items</p>
        <p class="text-2xl font-bold text-gray-900">{{ inventoryStore.items.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-gray-500 text-sm">Low Stock</p>
        <p class="text-2xl font-bold text-red-600">{{ inventoryStore.lowStockItems.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-gray-500 text-sm">Out of Stock</p>
        <p class="text-2xl font-bold text-gray-400">{{ inventoryStore.outOfStockItems.length }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-gray-500 text-sm">Total Value</p>
        <p class="text-2xl font-bold text-green-600">Rs. {{ inventoryStore.totalValue.toLocaleString() }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in inventoryStore.items" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ item.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ item.sku }}</td>
            <td class="px-6 py-4 whitespace-nowrap font-bold">{{ item.stock_quantity }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ item.unit }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStockClass(item)">{{ getStockStatus(item) }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
              <button @click="openAdjustModal(item)" class="text-blue-600 hover:text-blue-900 mr-3">Adjust</button>
              <button @click="openEditModal(item)" class="text-gray-600 hover:text-gray-900 mr-3">Edit</button>
              <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingItem ? 'Edit Item' : 'Add New Item'" size="md">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input v-model="formData.name" type="text" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
          <input v-model="formData.sku" type="text" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
            <input v-model.number="formData.stock_quantity" type="number" min="0" required class="w-full px-3 py-2 border rounded-lg">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Min Level *</label>
            <input v-model.number="formData.min_stock_level" type="number" min="0" required class="w-full px-3 py-2 border rounded-lg">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
            <input v-model="formData.unit" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="kg, pcs, liter">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
            <input v-model.number="formData.cost_price" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg">
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex gap-3">
          <button @click="showModal = false" class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
          <button @click="handleSubmit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            {{ editingItem ? 'Update' : 'Create' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Adjust Stock Modal -->
    <Modal v-model="showAdjustModal" title="Adjust Stock" size="sm">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
          <select v-model="adjustData.type" class="w-full px-3 py-2 border rounded-lg">
            <option value="add">Add Stock</option>
            <option value="subtract">Remove Stock</option>
            <option value="set">Set Stock</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
          <input v-model.number="adjustData.quantity" type="number" min="0" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
          <textarea v-model="adjustData.reason" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
        </div>
      </div>
      <template #footer>
        <div class="flex gap-3">
          <button @click="showAdjustModal = false" class="flex-1 px-4 py-2 border rounded-lg">Cancel</button>
          <button @click="handleAdjust" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg">Adjust</button>
        </div>
      </template>
    </Modal>

    <ConfirmDialog 
      v-model="showDeleteConfirm"
      type="danger"
      title="Delete Item?"
      :message="`Delete ${itemToDelete?.name}?`"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useInventoryStore } from '@/stores/inventory'
import Modal from '@/components/common/Modal.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const inventoryStore = useInventoryStore()
const showModal = ref(false)
const showAdjustModal = ref(false)
const showDeleteConfirm = ref(false)
const editingItem = ref(null)
const itemToDelete = ref(null)
const adjustingItem = ref(null)

const formData = ref({
  name: '',
  sku: '',
  stock_quantity: 0,
  min_stock_level: 0,
  unit: '',
  cost_price: 0,
})

const adjustData = ref({
  type: 'add',
  quantity: 0,
  reason: '',
})

function getStockStatus(item) {
  if (item.stock_quantity === 0) return 'Out of Stock'
  if (item.stock_quantity <= item.min_stock_level) return 'Low Stock'
  return 'In Stock'
}

function getStockClass(item) {
  if (item.stock_quantity === 0) return 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800'
  if (item.stock_quantity <= item.min_stock_level) return 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800'
  return 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800'
}

function openCreateModal() {
  editingItem.value = null
  formData.value = { name: '', sku: '', stock_quantity: 0, min_stock_level: 0, unit: '', cost_price: 0 }
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

function openAdjustModal(item) {
  adjustingItem.value = item
  adjustData.value = { type: 'add', quantity: 0, reason: '' }
  showAdjustModal.value = true
}

async function handleSubmit() {
  const result = editingItem.value
    ? await inventoryStore.updateItem(editingItem.value.id, formData.value)
    : await inventoryStore.createItem(formData.value)
  
  if (result.success) showModal.value = false
}

async function handleAdjust() {
  await inventoryStore.adjustStock(adjustingItem.value.id, adjustData.value.quantity, adjustData.value.type, adjustData.value.reason)
  showAdjustModal.value = false
}

function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteConfirm.value = true
}

async function handleDelete() {
  if (itemToDelete.value) {
    await inventoryStore.deleteItem(itemToDelete.value.id)
  }
}

onMounted(() => {
  inventoryStore.fetchInventory()
})
</script>