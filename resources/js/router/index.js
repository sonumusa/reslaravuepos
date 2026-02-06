import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Views
import WaiterPosView from '@/views/pos/WaiterPosView.vue';
import TableSelectView from '@/views/pos/TableSelectView.vue';
import CashierPOS from '@/views/cashier/CashierPOS.vue';
import KitchenDisplayView from '@/views/kds/KitchenDisplayView.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/pos/tables',
        name: 'table-select',
        component: TableSelectView,
        meta: { requiresAuth: true, role: 'waiter' }
    },
    {
        path: '/pos/waiter',
        name: 'waiter-pos',
        component: WaiterPosView,
        meta: { requiresAuth: true, role: 'waiter' }
    },
    {
        path: '/cashier',
        name: 'cashier-pos',
        component: CashierPOS,
        meta: { requiresAuth: true, role: 'cashier' }
    },
    {
        path: '/kds',
        name: 'kds',
        component: KitchenDisplayView,
        meta: { requiresAuth: true, role: 'kitchen' }
    },
    {
        path: '/kitchen',
        name: 'kitchen',
        component: () => import('@/views/kitchen/KitchenDisplay.vue'),
        meta: { requiresAuth: true, roles: ['kitchen', 'admin', 'superadmin'] }
    },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, roles: ['admin', 'superadmin'] },
        children: [
            { path: '', name: 'admin-dashboard', component: () => import('@/views/admin/Dashboard.vue') },
            { path: 'orders', name: 'admin-orders', component: () => import('@/views/admin/Orders.vue') },
            { path: 'menu', name: 'admin-menu', component: () => import('@/views/admin/Menu.vue') },
            { path: 'customers', name: 'admin-customers', component: () => import('@/views/admin/Customers.vue') },
            { path: 'inventory', name: 'admin-inventory', component: () => import('@/views/admin/Inventory.vue') },
            { path: 'reports', name: 'admin-reports', component: () => import('@/views/admin/Reports.vue') },
            { path: 'expenses', name: 'admin-expenses', component: () => import('@/views/admin/Expenses.vue') },
            { path: 'staff', name: 'admin-staff', component: () => import('@/views/admin/Staff.vue') },
            { path: 'branches', name: 'admin-branches', component: () => import('@/views/admin/BranchManagement.vue'), meta: { requiresAuth: true, roles: ['superadmin'] } },
            { path: 'users', name: 'admin-users', component: () => import('@/views/admin/UserManagement.vue'), meta: { requiresAuth: true, roles: ['superadmin', 'admin'] } },
            { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/SystemSettings.vue'), meta: { requiresAuth: true, roles: ['superadmin'] } },
            { path: 'system', name: 'admin-system', component: () => import('@/views/admin/SystemSettings.vue'), meta: { requiresAuth: true, roles: ['superadmin'] } }
        ]
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/components/auth/Login.vue')
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Initialize auth from localStorage if not already done
    if (!authStore.isAuthenticated && localStorage.getItem('auth_token')) {
        authStore.initAuth();
        await authStore.fetchUser();
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else {
        // Check for role permissions
        if (to.meta.role && !authStore.hasRole(to.meta.role)) {
            // User doesn't have the required role
            next({ path: '/' });
            return;
        }
        
        if (to.meta.roles && !authStore.hasRole(to.meta.roles)) {
            // User doesn't have any of the required roles
            next({ path: '/' });
            return;
        }
        
        next();
    }
});

export default router;
