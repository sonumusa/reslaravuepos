<template>
  <div class="user-management p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
        <p class="text-sm text-gray-500">Manage staff members and access</p>
      </div>
      <button 
        @click="showCreateModal = true" 
        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center gap-2" 
      >
        <UserPlusIcon class="w-5 h-5" />
        Add User
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
          <select 
            v-model="filters.branch_id" 
            class="w-full px-3 py-2 border rounded-lg" 
            @change="loadUsers" 
          >
            <option :value="null">All Branches</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
          <select 
            v-model="filters.role" 
            class="w-full px-3 py-2 border rounded-lg" 
            @change="loadUsers" 
          >
            <option :value="null">All Roles</option>
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
            <option value="waiter">Waiter</option>
            <option value="kitchen">Kitchen</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select 
            v-model="filters.is_active" 
            class="w-full px-3 py-2 border rounded-lg" 
            @change="loadUsers" 
          >
            <option :value="null">All</option>
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input 
            v-model="filters.search" 
            type="text" 
            placeholder="Search by name or email" 
            class="w-full px-3 py-2 border rounded-lg" 
            @input="debounceSearch" 
          />
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-4 text-sm font-medium text-gray-600">User</th>
            <th class="text-left p-4 text-sm font-medium text-gray-600">Branch</th>
            <th class="text-left p-4 text-sm font-medium text-gray-600">Role</th>
            <th class="text-left p-4 text-sm font-medium text-gray-600">Contact</th>
            <th class="text-left p-4 text-sm font-medium text-gray-600">Status</th>
            <th class="text-left p-4 text-sm font-medium text-gray-600">Last Login</th>
            <th class="text-right p-4 text-sm font-medium text-gray-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <span class="text-blue-600 font-bold">{{ getUserInitials(user) }}</span>
                </div>
                <div>
                  <p class="font-medium">{{ user.name }}</p>
                  <p class="text-sm text-gray-500">{{ user.email }}</p>
                </div>
              </div>
            </td>
            <td class="p-4">
              <span class="text-sm">{{ user.branch?.name || 'N/A' }}</span>
            </td>
            <td class="p-4">
              <span :class="getRoleBadgeClass(user.role)">
                {{ getRoleLabel(user.role) }}
              </span>
            </td>
            <td class="p-4">
              <p class="text-sm">{{ user.phone || 'N/A' }}</p>
            </td>
            <td class="p-4">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                user.is_active
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'
              ]">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="p-4">
              <span class="text-sm text-gray-500">
                {{ formatDate(user.last_login_at) || 'Never' }}
              </span>
            </td>
            <td class="p-4">
              <div class="flex justify-end gap-2">
                <button 
                  @click="editUser(user)" 
                  class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg" 
                  title="Edit"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button 
                  @click="resetPassword(user)" 
                  class="p-2 text-amber-500 hover:bg-amber-50 rounded-lg" 
                  title="Reset Password"
                >
                  <KeyIcon class="w-4 h-4" />
                </button>
                <button 
                  @click="toggleUserStatus(user)" 
                  class="p-2 text-gray-500 hover:bg-gray-50 rounded-lg" 
                  :title="user.is_active ? 'Deactivate' : 'Activate'"
                >
                  <NoSymbolIcon v-if="user.is_active" class="w-4 h-4" />
                  <CheckIcon v-else class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="users.length === 0" class="text-center py-12 text-gray-400">
        <UserGroupIcon class="w-16 h-16 mx-auto mb-4" />
        <p>No users found</p>
      </div>
    </div>

    <!-- User Form Modal -->
    <UserFormModal 
      v-if="showCreateModal || editingUser" 
      :user="editingUser" 
      :branches="branches" 
      @close="closeModal" 
      @save="handleSaveUser" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useBranchStore } from '@/stores/branch'
import { useAppStore } from '@/stores/app'
import api from '@/services/api'
import { formatDate } from '@/utils/formatters'
import UserFormModal from '@/components/admin/UserFormModal.vue'
import {
  UserPlusIcon,
  PencilIcon,
  KeyIcon,
  NoSymbolIcon, // BanIcon
  CheckIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'

const branchStore = useBranchStore()
const appStore = useAppStore()

const users = ref([])
const branches = ref([])
const showCreateModal = ref(false)
const editingUser = ref(null)

const filters = ref({
  branch_id: null,
  role: null,
  is_active: null,
  search: ''
})

let searchTimeout = null

async function loadUsers() {
  try {
    // const response = await api.get('/api/users', { params: filters.value })
    // users.value = response.data.data
    
    // Mock data for development
    await new Promise(resolve => setTimeout(resolve, 500))
    users.value = [
      {
        id: 1,
        name: 'Super Admin',
        email: 'admin@example.com',
        phone: '1234567890',
        role: 'superadmin',
        branch: { name: 'All Branches' },
        is_active: true,
        last_login_at: new Date().toISOString()
      },
      {
        id: 2,
        name: 'John Cashier',
        email: 'cashier@example.com',
        phone: '0987654321',
        role: 'cashier',
        branch: { name: 'Main Branch' },
        is_active: true,
        last_login_at: new Date(Date.now() - 86400000).toISOString()
      },
      {
        id: 3,
        name: 'Jane Waiter',
        email: 'waiter@example.com',
        phone: '1122334455',
        role: 'waiter',
        branch: { name: 'Downtown Branch' },
        is_active: false,
        last_login_at: null
      }
    ]
    
  } catch (error) {
    appStore.showError('Failed to load users')
  }
}

async function loadBranches() {
  branches.value = await branchStore.fetchBranches()
}

function debounceSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadUsers, 500)
}

function editUser(user) {
  editingUser.value = { ...user }
}

function closeModal() {
  showCreateModal.value = false
  editingUser.value = null
}

async function handleSaveUser(userData) {
  try {
    if (editingUser.value) {
      // await api.put(`/api/users/${editingUser.value.id}`, userData)
      await new Promise(resolve => setTimeout(resolve, 500))
      appStore.showSuccess('User updated successfully')
    } else {
      // await api.post('/api/users', userData)
      await new Promise(resolve => setTimeout(resolve, 500))
      appStore.showSuccess('User created successfully')
    }
    await loadUsers()
    closeModal()
  } catch (error) {
    appStore.showError(error.response?.data?.message || 'Failed to save user')
  }
}

async function toggleUserStatus(user) {
  try {
    // await api.patch(`/api/users/${user.id}`, {
    //   is_active: !user.is_active
    // })
    await new Promise(resolve => setTimeout(resolve, 500))
    user.is_active = !user.is_active
    appStore.showSuccess(`User ${user.is_active ? 'activated' : 'deactivated'}`)
    // await loadUsers()
  } catch (error) {
    appStore.showError('Failed to update user status')
  }
}

async function resetPassword(user) {
  const newPassword = prompt(`Enter new password for ${user.name}:`)
  if (!newPassword) return

  try {
    // await api.post(`/api/users/${user.id}/reset-password`, {
    //   password: newPassword
    // })
    await new Promise(resolve => setTimeout(resolve, 500))
    appStore.showSuccess('Password reset successfully')
  } catch (error) {
    appStore.showError('Failed to reset password')
  }
}

function getUserInitials(user) {
  if (!user.name) return '?'
  return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function getRoleLabel(role) {
  const labels = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    cashier: 'Cashier',
    waiter: 'Waiter',
    kitchen: 'Kitchen'
  }
  return labels[role] || role
}

function getRoleBadgeClass(role) {
  const classes = {
    superadmin: 'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700',
    admin: 'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700',
    cashier: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-700',
    waiter: 'px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700',
    kitchen: 'px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700'
  }
  return classes[role] || 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700'
}

onMounted(() => {
  loadBranches()
  loadUsers()
})
</script>
