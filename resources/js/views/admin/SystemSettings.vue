<template>
  <div class="system-settings p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">System Settings</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Settings Menu -->
      <div class="bg-white rounded-lg shadow-sm p-4">
        <nav class="space-y-2">
          <button
            v-for="section in sections"
            :key="section.id"
            @click="activeSection = section.id"
            :class="[
              'w-full text-left px-4 py-3 rounded-lg transition flex items-center gap-3',
              activeSection === section.id
                ? 'bg-blue-50 text-blue-700'
                : 'hover:bg-gray-50 text-gray-700'
            ]"
          >
            <component :is="section.icon" class="w-5 h-5" />
            {{ section.label }}
          </button>
        </nav>
      </div>

      <!-- Settings Content -->
      <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
        <!-- PRA Settings -->
        <div v-if="activeSection === 'pra'" class="space-y-4">
          <h2 class="text-xl font-bold mb-4">PRA Configuration</h2>
          
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="settings.pra.enabled"
                type="checkbox"
                class="w-5 h-5 text-blue-500 rounded"
              />
              <span class="font-medium">Enable PRA Integration</span>
            </label>
          </div>

          <div v-if="settings.pra.enabled">
            <label class="block text-sm font-medium text-gray-700 mb-2">API URL</label>
            <input
              v-model="settings.pra.api_url"
              type="url"
              class="w-full px-3 py-2 border rounded-lg"
              placeholder=" `https://api.pra.punjab.gov.pk/v1` "
            />
          </div>

          <div v-if="settings.pra.enabled">
            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
            <input
              v-model="settings.pra.api_key"
              type="password"
              class="w-full px-3 py-2 border rounded-lg"
            />
          </div>

          <div v-if="settings.pra.enabled">
            <label class="block text-sm font-medium text-gray-700 mb-2">NTN Number</label>
            <input
              v-model="settings.pra.ntn_number"
              type="text"
              class="w-full px-3 py-2 border rounded-lg"
            />
          </div>

          <button
            @click="testPraConnection"
            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
          >
            Test Connection
          </button>
        </div>

        <!-- General Settings -->
        <div v-if="activeSection === 'general'" class="space-y-4">
          <h2 class="text-xl font-bold mb-4">General Settings</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
            <input
              v-model="settings.general.company_name"
              type="text"
              class="w-full px-3 py-2 border rounded-lg"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
            <select
              v-model="settings.general.currency"
              class="w-full px-3 py-2 border rounded-lg"
            >
              <option value="PKR">Pakistani Rupee (PKR)</option>
              <option value="USD">US Dollar (USD)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time Zone</label>
            <select
              v-model="settings.general.timezone"
              class="w-full px-3 py-2 border rounded-lg"
            >
              <option value="Asia/Karachi">Asia/Karachi</option>
              <option value="UTC">UTC</option>
            </select>
          </div>
        </div>

        <!-- POS Settings -->
        <div v-if="activeSection === 'pos'" class="space-y-4">
          <h2 class="text-xl font-bold mb-4">POS Settings</h2>

          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="settings.pos.offline_mode"
                type="checkbox"
                class="w-5 h-5 text-blue-500 rounded"
              />
              <span class="font-medium">Enable Offline Mode</span>
            </label>
          </div>

          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="settings.pos.auto_print_receipt"
                type="checkbox"
                class="w-5 h-5 text-blue-500 rounded"
              />
              <span class="font-medium">Auto Print Receipt</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Receipt Printer Type
            </label>
            <select
              v-model="settings.pos.printer_type"
              class="w-full px-3 py-2 border rounded-lg"
            >
              <option value="thermal">Thermal Printer</option>
              <option value="browser">Browser Print</option>
            </select>
          </div>
        </div>

        <!-- Save Button -->
        <div class="mt-6 pt-6 border-t flex justify-end">
          <button
            @click="saveSettings"
            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
          >
            Save Settings
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { 
  Cog6ToothIcon, 
  ComputerDesktopIcon, 
  DocumentTextIcon 
} from '@heroicons/vue/24/outline'

const appStore = useAppStore()
const activeSection = ref('general')

const sections = [
  { id: 'general', label: 'General', icon: Cog6ToothIcon },
  { id: 'pos', label: 'POS Configuration', icon: ComputerDesktopIcon },
  { id: 'pra', label: 'PRA Integration', icon: DocumentTextIcon }
]

const settings = ref({
  general: {
    company_name: 'Khan Shinwari Hujra',
    currency: 'PKR',
    timezone: 'Asia/Karachi'
  },
  pos: {
    offline_mode: true,
    auto_print_receipt: true,
    printer_type: 'thermal'
  },
  pra: {
    enabled: false,
    api_url: '',
    api_key: '',
    ntn_number: ''
  }
})

async function saveSettings() {
  try {
    // await api.post('/api/settings', settings.value)
    await new Promise(resolve => setTimeout(resolve, 500))
    appStore.showSuccess('Settings saved successfully')
  } catch (error) {
    appStore.showError('Failed to save settings')
  }
}

async function testPraConnection() {
  try {
    // await api.post('/api/pra/test-connection', settings.value.pra)
    await new Promise(resolve => setTimeout(resolve, 1000))
    appStore.showSuccess('PRA Connection Successful')
  } catch (error) {
    appStore.showError('PRA Connection Failed')
  }
}
</script>
