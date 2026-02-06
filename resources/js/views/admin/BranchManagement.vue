<template>
  <div class="branch-management p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Branch Management</h1>
        <p class="text-sm text-gray-500">Manage all restaurant branches</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Add Branch
      </button>
    </div>

    <!-- Branch Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="branch in branches"
        :key="branch.id"
        :class="[
          'bg-white rounded-xl shadow-sm border-2 p-6 transition',
          branch.is_active ? 'border-green-200' : 'border-gray-200'
        ]"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div :class="[
              'w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-xl',
              branch.is_active ? 'bg-green-500' : 'bg-gray-400'
            ]">
              {{ branch.code }}
            </div>
            <div>
              <h3 class="font-bold text-lg">{{ branch.name }}</h3>
              <span :class="[
                'text-xs px-2 py-1 rounded-full',
                branch.is_active
                  ? 'bg-green-100 text-green-700'
                  : 'bg-gray-100 text-gray-600'
              ]">
                {{ branch.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
          <div class="flex gap-2">
            <button
              @click="editBranch(branch)"
              class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg"
            >
              <PencilIcon class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(branch)"
              class="p-2 text-red-500 hover:bg-red-50 rounded-lg"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="space-y-2 text-sm">
          <div class="flex items-center gap-2 text-gray-600">
            <MapPinIcon class="w-4 h-4" />
            <span>{{ branch.city }}</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <PhoneIcon class="w-4 h-4" />
            <span>{{ branch.phone }}</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <DocumentTextIcon class="w-4 h-4" />
            <span>NTN: {{ branch.ntn_number }}</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600">
            <ReceiptPercentIcon class="w-4 h-4" />
            <span>GST: {{ branch.gst_rate }}%</span>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t grid grid-cols-3 gap-2 text-center">
          <div>
            <p class="text-xs text-gray-500">Terminals</p>
            <p class="text-lg font-bold">{{ branch.terminals_count || 0 }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Staff</p>
            <p class="text-lg font-bold">{{ branch.users_count || 0 }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Tables</p>
            <p class="text-lg font-bold">{{ branch.tables_count || 0 }}</p>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <button
            @click="viewBranchDetails(branch)"
            class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm"
          >
            View Details
          </button>
          <button
            @click="manageBranchSettings(branch)"
            class="flex-1 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm"
          >
            Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Branch Modal -->
    <BranchFormModal
      v-if="showCreateModal || editingBranch"
      :branch="editingBranch"
      @close="closeModal"
      @save="handleSaveBranch"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useBranchStore } from '@/stores/branch'
import { useAppStore } from '@/stores/app'
import BranchFormModal from '@/components/admin/BranchFormModal.vue'
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  MapPinIcon,
  PhoneIcon,
  DocumentTextIcon,
  ReceiptPercentIcon
} from '@heroicons/vue/24/outline'

const branchStore = useBranchStore()
const appStore = useAppStore()

const showCreateModal = ref(false)
const editingBranch = ref(null)
const branches = ref([])

async function loadBranches() {
  try {
    branches.value = await branchStore.fetchBranches()
  } catch (error) {
    appStore.showError('Failed to load branches')
  }
}

function editBranch(branch) {
  editingBranch.value = { ...branch }
}

function closeModal() {
  showCreateModal.value = false
  editingBranch.value = null
}

async function handleSaveBranch(branchData) {
  try {
    if (editingBranch.value) {
      await branchStore.updateBranch(editingBranch.value.id, branchData)
      appStore.showSuccess('Branch updated successfully')
    } else {
      await branchStore.createBranch(branchData)
      appStore.showSuccess('Branch created successfully')
    }
    await loadBranches()
    closeModal()
  } catch (error) {
    appStore.showError(error.message || 'Failed to save branch')
  }
}

function confirmDelete(branch) {
  if (confirm(`Are you sure you want to delete ${branch.name}?`)) {
    deleteBranch(branch)
  }
}

async function deleteBranch(branch) {
  try {
    await branchStore.deleteBranch(branch.id)
    appStore.showSuccess('Branch deleted successfully')
    await loadBranches()
  } catch (error) {
    appStore.showError('Failed to delete branch')
  }
}

function viewBranchDetails(branch) {
  // Navigate to branch details view
  console.log('View details for:', branch.name)
}

function manageBranchSettings(branch) {
  // Open branch settings modal
  console.log('Manage settings for:', branch.name)
}

onMounted(() => {
  loadBranches()
})
</script>
