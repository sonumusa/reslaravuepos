<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-md text-white">
      <div class="p-4 border-b border-gray-700 flex justify-between items-center">
        <h2 class="text-xl font-bold">KDS Settings</h2>
        <button @click="$emit('close')" class="p-2 hover:bg-gray-700 rounded-lg">
          <i class="fas fa-times w-6 h-6"></i>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <!-- Sound Settings -->
        <div>
          <h3 class="font-medium mb-3">Sound Alerts</h3>
          <div class="space-y-2">
            <label class="flex items-center justify-between cursor-pointer">
              <span>Enable Sound</span>
              <input 
                type="checkbox" 
                v-model="settings.soundEnabled" 
                class="w-5 h-5 rounded" 
              />
            </label>
            <label class="flex items-center justify-between cursor-pointer">
              <span>New Order Alert</span> 
              <input 
                type="checkbox" 
                v-model="settings.newOrderSound" 
                class="w-5 h-5 rounded" 
              />
            </label>
          </div>
        </div>

        <!-- Display Settings -->
        <div>
          <h3 class="font-medium mb-3">Display</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm text-gray-400">Warning Time (minutes)</label>
              <input 
                type="number" 
                v-model.number="settings.warningTime" 
                class="w-full p-2 bg-gray-700 rounded-lg mt-1" 
              />
            </div>
            <div>
              <label class="text-sm text-gray-400">Urgent Time (minutes)</label>
              <input 
                type="number" 
                v-model.number="settings.urgentTime" 
                class="w-full p-2 bg-gray-700 rounded-lg mt-1" 
              />
            </div>
            <div>
              <label class="text-sm text-gray-400">Columns</label> 
              <select 
                v-model.number="settings.columns" 
                class="w-full p-2 bg-gray-700 rounded-lg mt-1" 
              >
                <option :value="3">3 Columns</option> 
                <option :value="4">4 Columns</option> 
                <option :value="5">5 Columns</option> 
              </select>
            </div>
          </div>
        </div>

        <!-- Auto Settings -->
        <div>
          <h3 class="font-medium mb-3">Automation</h3> 
          <div class="space-y-2">
            <label class="flex items-center justify-between cursor-pointer">
              <span>Auto-hide completed orders</span> 
              <input 
                type="checkbox" 
                v-model="settings.autoHideCompleted" 
                class="w-5 h-5 rounded" 
              />
            </label>
            <div v-if="settings.autoHideCompleted">
              <label class="text-sm text-gray-400">Hide after (seconds)</label> 
              <input 
                type="number" 
                v-model.number="settings.hideAfterSeconds" 
                class="w-full p-2 bg-gray-700 rounded-lg mt-1" 
              />
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 border-t border-gray-700 flex gap-3">
        <button 
          @click="$emit('close')" 
          class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg"
        >
          Cancel
        </button>
        <button 
          @click="saveSettings" 
          class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-lg"
        >
          Save Settings
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['close', 'save'])

const settings = ref({
  soundEnabled: true,
  newOrderSound: true,
  warningTime: 10,
  urgentTime: 15,
  columns: 4,
  autoHideCompleted: true,
  hideAfterSeconds: 30
})

function saveSettings() {
  emit('save', { ...settings.value })
}

onMounted(() => {
  const saved = localStorage.getItem('kds_settings')
  if (saved) {
    settings.value = { ...settings.value, ...JSON.parse(saved) }
  }
})
</script>