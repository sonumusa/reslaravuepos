<template>
  <div class="admin-layout min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside 
      :class="[ 
        'fixed left-0 top-0 h-full bg-slate-800 text-white transition-all duration-300 z-40', 
        sidebarExpanded ? 'w-64' : 'w-20' 
      ]"
    >
      <div class="p-4 flex items-center justify-between border-b border-slate-700">
        <h1 v-if="sidebarExpanded" class="text-xl font-bold">ResLaraVuePOS</h1>
        <button @click="sidebarExpanded = !sidebarExpanded" class="p-2 hover:bg-slate-700 rounded">
          <Bars3Icon class="w-5 h-5" />
        </button>
      </div>

      <nav class="p-4">
        <ul class="space-y-2">
          <li v-for="item in menuItems" :key="item.name">
            <router-link 
              :to="item.to" 
              :class="[ 
                'flex items-center gap-3 px-3 py-2 rounded-lg transition', 
                isActive(item.to) 
                  ? 'bg-blue-600 text-white' 
                  : 'text-slate-300 hover:bg-slate-700' 
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
              <span v-if="sidebarExpanded">{{ item.name }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
            {{ userInitials }}
          </div>
          <div v-if="sidebarExpanded" class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ currentUser?.name }}</p>
            <p class="text-xs text-slate-400 truncate">{{ currentUser?.role }}</p>
          </div>
          <button @click="handleLogout" class="p-2 hover:bg-slate-700 rounded">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main :class="['transition-all duration-300', sidebarExpanded ? 'ml-64' : 'ml-20']">
      <!-- Top Bar -->
      <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">{{ currentPageTitle }}</h2>
          <p class="text-sm text-gray-500">{{ currentBranch?.name || 'All Branches' }}</p>
        </div>
        
        <div class="flex items-center gap-4">
           <slot name="header-actions"></slot>
        </div>
      </header>

      <div class="p-6">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  ClipboardDocumentListIcon,
  Bars3Icon, 
  ArrowRightOnRectangleIcon,
  Bars3BottomLeftIcon,
  UsersIcon,
  CubeIcon,
  ChartBarIcon,
  BanknotesIcon,
  UserGroupIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const sidebarExpanded = ref(true)

const currentUser = computed(() => authStore.user)
const currentBranch = computed(() => authStore.branch)

const userInitials = computed(() => {
  if (!currentUser.value?.name) return '?'
  return currentUser.value.name.split(' ').map(n => n[0]).join('').toUpperCase()
})

const currentPageTitle = computed(() => {
  const item = menuItems.find(i => isActive(i.to))
  return item ? item.name : 'Dashboard'
})

const menuItems = [
  { name: 'Dashboard', to: '/admin', icon: HomeIcon },
  { name: 'Orders', to: '/admin/orders', icon: ClipboardDocumentListIcon },
  { name: 'Menu', to: '/admin/menu', icon: Bars3BottomLeftIcon },
  { name: 'Customers', to: '/admin/customers', icon: UsersIcon },
  { name: 'Inventory', to: '/admin/inventory', icon: CubeIcon },
  { name: 'Reports', to: '/admin/reports', icon: ChartBarIcon },
  { name: 'Expenses', to: '/admin/expenses', icon: BanknotesIcon },
  { name: 'Staff', to: '/admin/staff', icon: UserGroupIcon },
  { name: 'Settings', to: '/admin/settings', icon: CogIcon },
]

function isActive(path) {
  if (path === '/admin' && route.path === '/admin') return true
  if (path !== '/admin' && route.path.startsWith(path)) return true
  return false
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
