<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Menu Management</h1>
      <button class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <PlusIcon class="w-5 h-5" />
        <span>Add Item</span>
      </button>
    </div>

    <!-- Categories Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2">
      <button 
        v-for="category in categories" 
        :key="category.id"
        @click="selectedCategory = category.id"
        :class="[
          'px-4 py-2 rounded-lg whitespace-nowrap transition-colors',
          selectedCategory === category.id 
            ? 'bg-blue-600 text-white' 
            : 'bg-white text-gray-600 hover:bg-gray-50 border'
        ]"
      >
        {{ category.name }}
      </button>
    </div>

    <!-- Menu Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <div v-for="item in filteredItems" :key="item.id" class="bg-white rounded-xl shadow overflow-hidden group">
        <div class="aspect-video bg-gray-100 relative">
          <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
            <PhotoIcon class="w-12 h-12" />
          </div>
          <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
            <button class="p-2 bg-white rounded-full hover:bg-gray-100">
              <PencilIcon class="w-5 h-5 text-blue-600" />
            </button>
            <button class="p-2 bg-white rounded-full hover:bg-gray-100">
              <TrashIcon class="w-5 h-5 text-red-600" />
            </button>
          </div>
        </div>
        <div class="p-4">
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-semibold text-gray-900">{{ item.name }}</h3>
            <span class="font-bold text-blue-600">{{ formatCurrency(item.price) }}</span>
          </div>
          <p class="text-sm text-gray-500 line-clamp-2">{{ item.description }}</p>
          <div class="mt-4 flex items-center justify-between text-sm">
            <span :class="item.is_available ? 'text-green-600' : 'text-red-600'">
              {{ item.is_available ? 'Available' : 'Unavailable' }}
            </span>
            <span class="text-gray-400">SKU: {{ item.sku }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, PhotoIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { formatCurrency } from '@/utils/formatters'
import { useMenuStore } from '@/stores/menu'

const menuStore = useMenuStore()
const categories = ref([])
const items = ref([])
const selectedCategory = ref(null)

const filteredItems = computed(() => {
  if (!selectedCategory.value) return items.value
  return items.value.filter(item => item.category_id === selectedCategory.value)
})

onMounted(async () => {
  await menuStore.fetchMenu()
  categories.value = menuStore.categories
  items.value = menuStore.menuItems
  if (categories.value.length) {
    selectedCategory.value = categories.value[0].id
  }
})
</script>
