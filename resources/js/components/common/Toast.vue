<script setup>
import { useAppStore } from '@/stores/app'
import { XMarkIcon, CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const appStore = useAppStore()

const icons = {
  success: CheckCircleIcon,
  error: ExclamationCircleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon
}

const colors = {
  success: 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800',
  error: 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
  warning: 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800',
  info: 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800'
}

const iconColors = {
  success: 'text-green-500',
  error: 'text-red-500',
  warning: 'text-yellow-500',
  info: 'text-blue-500'
}

function remove(id) {
  appStore.removeNotification(id)
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm pointer-events-none">
    <TransitionGroup 
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-for="notification in appStore.notifications.filter(n => !n.read)" 
        :key="notification.id"
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg ring-1 ring-black ring-opacity-5"
        :class="colors[notification.type] || colors.info"
      >
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <component 
                :is="icons[notification.type] || icons.info" 
                class="h-6 w-6" 
                :class="iconColors[notification.type]" 
                aria-hidden="true" 
              />
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
              <p class="text-sm font-medium">{{ notification.message }}</p>
            </div>
            <div class="ml-4 flex flex-shrink-0">
              <button 
                type="button" 
                @click="remove(notification.id)"
                class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="[
                  notification.type === 'success' ? 'text-green-500 hover:bg-green-100 focus:ring-green-600' : 
                  notification.type === 'error' ? 'text-red-500 hover:bg-red-100 focus:ring-red-600' : 
                  notification.type === 'warning' ? 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600' : 
                  'text-blue-500 hover:bg-blue-100 focus:ring-blue-600'
                ]"
              >
                <span class="sr-only">Close</span>
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
        <!-- Progress bar for auto-close -->
        <div v-if="notification.autoClose" class="h-1 w-full bg-gray-200 bg-opacity-30">
          <div 
            class="h-full" 
            :class="[
               notification.type === 'success' ? 'bg-green-500' : 
               notification.type === 'error' ? 'bg-red-500' : 
               notification.type === 'warning' ? 'bg-yellow-500' : 
               'bg-blue-500'
            ]"
            :style="{ 
              width: '100%', 
              animation: `shrink ${notification.duration}ms linear forwards` 
            }"
          ></div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style>
@keyframes shrink {
  from { width: 100%; }
  to { width: 0%; }
}
</style>
