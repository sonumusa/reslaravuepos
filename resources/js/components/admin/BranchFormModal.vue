<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
        <h2 class="text-xl font-bold">
          {{ branch ? 'Edit Branch' : 'Create New Branch' }}
        </h2>
        <button @click="$emit('close')" class="p-2 hover:bg-blue-600 rounded-full">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
        <div class="grid grid-cols-2 gap-4">
          <!-- Branch Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Branch Code *
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              maxlength="10"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., BR001"
            />
          </div>

          <!-- Branch Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Branch Name *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., Downtown Branch"
            />
          </div>

          <!-- Address (Full Width) -->
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Address *
            </label>
            <textarea
              v-model="form.address"
              required
              rows="2"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Full address"
            ></textarea>
          </div>

          <!-- City -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              City *
            </label>
            <input
              v-model="form.city"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., Lahore"
            />
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Phone *
            </label>
            <input
              v-model="form.phone"
              type="tel"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., 0300-1234567"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email
            </label>
            <input
              v-model="form.email"
              type="email"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="branch@example.com"
            />
          </div>

          <!-- NTN Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              NTN Number *
            </label>
            <input
              v-model="form.ntn_number"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., 1234567-8"
            />
          </div>

          <!-- STRN Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              STRN Number
            </label>
            <input
              v-model="form.strn_number"
              type="text"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Sales Tax Registration"
            />
          </div>

          <!-- GST Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              GST Rate (%) *
            </label>
            <input
              v-model.number="form.gst_rate"
              type="number"
              step="0.01"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., 16.00"
            />
          </div>

          <!-- Active Status -->
          <div class="col-span-2">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="w-5 h-5 text-blue-500 rounded focus:ring-2 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">Active</span>
            </label>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 mt-6">
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
            {{ branch ? 'Update' : 'Create' }} Branch
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
  branch: { type: Object, default: null }
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  code: '',
  name: '',
  address: '',
  city: '',
  phone: '',
  email: '',
  ntn_number: '',
  strn_number: '',
  gst_rate: 16.00,
  is_active: true
})

function handleSubmit() {
  emit('save', { ...form.value })
}

onMounted(() => {
  if (props.branch) {
    form.value = { ...props.branch }
  }
})
</script>
