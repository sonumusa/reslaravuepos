<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Expense Tracking</h1>
      <button 
        @click="openCreateModal"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        <PlusIcon class="w-5 h-5" />
        <span>Add Expense</span>
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-red-500">
        <p class="text-gray-500 text-sm">Total Expenses</p>
        <p class="text-2xl font-bold text-gray-900">Rs. {{ expenseStore.totalExpenses.toLocaleString() }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
        <p class="text-gray-500 text-sm">This Month</p>
        <p class="text-2xl font-bold text-gray-900">Rs. {{ monthTotal.toLocaleString() }}</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-yellow-500">
        <p class="text-gray-500 text-sm">Categories</p>
        <p class="text-2xl font-bold text-gray-900">{{ expenseStore.categories.length }}</p>
      </div>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="expense in expenseStore.expenses" :key="expense.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(expense.expense_date) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                {{ expense.category?.name || 'Uncategorized' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ expense.description || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Rs. {{ parseFloat(expense.amount).toLocaleString() }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
              <button @click="openEditModal(expense)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
              <button @click="confirmDelete(expense)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingExpense ? 'Edit Expense' : 'Add Expense'" size="md">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
          <select 
            v-model="formData.expense_category_id" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select Category</option>
            <option v-for="cat in expenseStore.categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
          <input 
            v-model.number="formData.amount" 
            type="number" 
            min="0" 
            step="0.01" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500"
            placeholder="0.00"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
          <input 
            v-model="formData.expense_date" 
            type="date" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
          <select 
            v-model="formData.payment_method" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select Method</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea 
            v-model="formData.description" 
            rows="3" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-blue-500"
            placeholder="Additional notes..."
          ></textarea>
        </div>

        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      </form>

      <template #footer>
        <div class="flex gap-3">
          <button @click="showModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button @click="handleSubmit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            {{ editingExpense ? 'Update' : 'Create' }}
          </button>
        </div>
      </template>
    </Modal>

    <ConfirmDialog 
      v-model="showDeleteConfirm"
      type="danger"
      title="Delete Expense?"
      :message="`Delete this expense of Rs. ${expenseToDelete?.amount}?`"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useExpenseStore } from '@/stores/expense'
import Modal from '@/components/common/Modal.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const expenseStore = useExpenseStore()
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const editingExpense = ref(null)
const expenseToDelete = ref(null)
const error = ref(null)

const formData = ref({
  expense_category_id: '',
  amount: 0,
  expense_date: new Date().toISOString().split('T')[0],
  payment_method: '',
  description: '',
})

const monthTotal = computed(() => {
  const thisMonth = new Date().getMonth()
  const thisYear = new Date().getFullYear()
  return expenseStore.expenses
    .filter(e => {
      const date = new Date(e.expense_date)
      return date.getMonth() === thisMonth && date.getFullYear() === thisYear
    })
    .reduce((sum, e) => sum + parseFloat(e.amount || 0), 0)
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

function openCreateModal() {
  editingExpense.value = null
  formData.value = {
    expense_category_id: '',
    amount: 0,
    expense_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    description: '',
  }
  error.value = null
  showModal.value = true
}

function openEditModal(expense) {
  editingExpense.value = expense
  formData.value = {
    expense_category_id: expense.expense_category_id,
    amount: expense.amount,
    expense_date: expense.expense_date,
    payment_method: expense.payment_method || '',
    description: expense.description || '',
  }
  error.value = null
  showModal.value = true
}

async function handleSubmit() {
  error.value = null
  
  const result = editingExpense.value
    ? await expenseStore.updateExpense(editingExpense.value.id, formData.value)
    : await expenseStore.createExpense(formData.value)

  if (result.success) {
    showModal.value = false
  } else {
    error.value = result.error
  }
}

function confirmDelete(expense) {
  expenseToDelete.value = expense
  showDeleteConfirm.value = true
}

async function handleDelete() {
  if (expenseToDelete.value) {
    await expenseStore.deleteExpense(expenseToDelete.value.id)
  }
}

onMounted(async () => {
  await expenseStore.fetchCategories()
  await expenseStore.fetchExpenses()
})
</script>