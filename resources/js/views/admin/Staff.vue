<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Staff Management</h1>
      <button 
        @click="openCreateModal"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        <PlusIcon class="w-5 h-5" />
        <span>Add Staff</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="userStore.isLoading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Staff Table -->
    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in userStore.users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getRoleClass(user.role)">{{ user.role }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ user.email }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ user.branch?.name || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="openEditModal(user)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
              <button @click="confirmDelete(user)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingUser ? 'Edit Staff' : 'Add New Staff'" size="md">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input 
            v-model="formData.name" 
            type="text" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input 
            v-model="formData.email" 
            type="email" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password {{ editingUser ? '' : '*' }}</label>
          <input 
            v-model="formData.password" 
            type="password" 
            :required="!editingUser" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :placeholder="editingUser ? 'Leave blank to keep current' : ''"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
          <select 
            v-model="formData.role" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Role</option>
            <option value="superadmin">Super Admin</option>
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
            <option value="waiter">Waiter</option>
            <option value="kitchen">Kitchen</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
          <select 
            v-model="formData.branch_id" 
            required 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Branch</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">PIN (4 digits)</label>
          <input 
            v-model="formData.pin" 
            type="text" 
            maxlength="4" 
            pattern="[0-9]{4}"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Optional 4-digit PIN"
          >
        </div>

        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      </form>

      <template #footer>
        <div class="flex gap-3">
          <button @click="showModal = false" type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button @click="handleSubmit" type="button" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            {{ editingUser ? 'Update' : 'Create' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Confirm Delete -->
    <ConfirmDialog 
      v-model="showDeleteConfirm"
      type="danger"
      title="Delete Staff Member?"
      :message="`Are you sure you want to delete ${userToDelete?.name}? This cannot be undone.`"
      confirm-text="Delete"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useUserStore } from '@/stores/user'
import { useBranchStore } from '@/stores/branch'
import Modal from '@/components/common/Modal.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const userStore = useUserStore()
const branchStore = useBranchStore()

const showModal = ref(false)
const showDeleteConfirm = ref(false)
const editingUser = ref(null)
const userToDelete = ref(null)
const error = ref(null)
const branches = ref([])

const formData = ref({
  name: '',
  email: '',
  password: '',
  role: '',
  branch_id: '',
  pin: '',
})

function getRoleClass(role) {
  const classes = {
    superadmin: 'px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800',
    admin: 'px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800',
    cashier: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800',
    waiter: 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800',
    kitchen: 'px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800',
  }
  return classes[role] || 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800'
}

function openCreateModal() {
  editingUser.value = null
  formData.value = {
    name: '',
    email: '',
    password: '',
    role: '',
    branch_id: '',
    pin: '',
  }
  error.value = null
  showModal.value = true
}

function openEditModal(user) {
  editingUser.value = user
  formData.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    branch_id: user.branch_id,
    pin: user.pin || '',
  }
  error.value = null
  showModal.value = true
}

async function handleSubmit() {
  error.value = null
  
  const result = editingUser.value
    ? await userStore.updateUser(editingUser.value.id, formData.value)
    : await userStore.createUser(formData.value)

  if (result.success) {
    showModal.value = false
    console.log('Staff saved successfully')
  } else {
    error.value = result.error
  }
}

function confirmDelete(user) {
  userToDelete.value = user
  showDeleteConfirm.value = true
}

async function handleDelete() {
  if (userToDelete.value) {
    await userStore.deleteUser(userToDelete.value.id)
    userToDelete.value = null
  }
}

onMounted(async () => {
  await userStore.fetchUsers()
  const branchesData = await branchStore.fetchBranches()
  branches.value = branchesData || []
})
</script>