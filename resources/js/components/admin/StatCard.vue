<template>
  <div :class="['bg-white rounded-xl shadow p-6', alert ? 'ring-2 ring-amber-400' : '']">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm text-gray-500 mb-1">{{ title }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ value }}</p>
        <div v-if="change !== undefined" class="flex items-center mt-2">
          <span :class="changeClass">
            <component 
              :is="change > 0 ? ArrowUpIcon : ArrowDownIcon" 
              class="w-4 h-4 mr-1"
            />
            {{ Math.abs(change) }}%
          </span>
          <span class="text-xs text-gray-400 ml-1">vs yesterday</span>
        </div>
      </div>
      <div :class="['p-3 rounded-lg', colorClasses.bg]">
        <component :is="icons[icon]" :class="['w-6 h-6', colorClasses.text]" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
  ArrowUpIcon, 
  ArrowDownIcon, 
  CurrencyDollarIcon, 
  ClipboardDocumentListIcon, 
  CalculatorIcon, 
  DocumentTextIcon, 
  ChartBarIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },
  change: { type: Number, default: undefined },
  icon: { type: String, default: 'ChartIcon' },
  color: { type: String, default: 'blue' },
  alert: { type: Boolean, default: false }
})

const icons = {
  CurrencyIcon: CurrencyDollarIcon,
  ClipboardIcon: ClipboardDocumentListIcon,
  CalculatorIcon: CalculatorIcon,
  DocumentIcon: DocumentTextIcon,
  ChartIcon: ChartBarIcon
}

const colorClasses = computed(() => {
  const colors = {
    blue: { bg: 'bg-blue-100', text: 'text-blue-600' },
    green: { bg: 'bg-green-100', text: 'text-green-600' },
    purple: { bg: 'bg-purple-100', text: 'text-purple-600' },
    amber: { bg: 'bg-amber-100', text: 'text-amber-600' },
    red: { bg: 'bg-red-100', text: 'text-red-600' }
  }
  return colors[props.color] || colors.blue
})

const changeClass = computed(() => {
  if (props.change > 0) return 'flex items-center text-sm text-green-600'
  if (props.change < 0) return 'flex items-center text-sm text-red-600'
  return 'flex items-center text-sm text-gray-600'
})
</script>
