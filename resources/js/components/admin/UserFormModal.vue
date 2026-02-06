<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
      <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
        <h2 class="text-xl font-bold">
          {{ user ? 'Edit User' : 'Create New User' }}
        </h2>
        <button @click="$emit('close')" class="p-2 hover:bg-blue-600 rounded-full">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
          <input
            v-model="form.phone"
            type="tel"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
          <select
            v-model="form.branch_id"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">Select Branch</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
          <select
            v-model="form.role"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
            <option value="waiter">Waiter</option>
            <option value="kitchen">Kitchen</option>
          </select>
        </div>

        <div v-if="!user">
          <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
          <input
            v-model="form.password"
            type="password"
            :required="!user"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">PIN (4 digits)</label>
          <input
            v-model="form.pin"
            type="text"
            maxlength="4"
            pattern="\d{4}"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="1234"
          />
          <p class="text-xs text-gray-500 mt-1">For quick POS login</p>
        </div>

        <div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="w-5 h-5 text-blue-500 rounded"
            />
            <span class="text-sm font-medium text-gray-700">Active</span>
          </label>
        </div>

        <div class="flex gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
          >
            {{ user ? 'Update' : 'Create' }} User
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  user: { type: Object, default: null },
  branches: { type: Array, required: true }
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  name: '',
  email: '',
  phone: '',
  branch_id: null,
  role: 'waiter',
  password: '',
  pin: '',
  is_active: true
})

function handleSubmit() {
  const data = { ...form.value }
  if (props.user && !data.password) {
    delete data.password
  }
  emit('save', data)
}

onMounted(() => {
  if (props.user) {
    form.value = {
      ...props.user,
      password: ''
    }
  }
})
</script>
