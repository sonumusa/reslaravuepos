import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import { useOfflineDb } from '@/services/offline-db';
import { useAuthStore } from './auth';

export const useMenuStore = defineStore('menu', () => {
    const authStore = useAuthStore();

    // ═══════════════════════════════════════════════════════
    // STATE
    // ═══════════════════════════════════════════════════════
    const categories = ref([]);
    const menuItems = ref([]);
    const modifiers = ref([]);
    const dailySpecials = ref([]);
    const selectedCategory = ref('all');
    const searchQuery = ref('');
    const isLoading = ref(false);
    const lastFetched = ref(null);
    const error = ref(null);

    // ═══════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════
    const activeCategories = computed(() => 
        categories.value
            .filter(c => c.is_active)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
    );

    const availableItems = computed(() => 
        menuItems.value.filter(item => item.is_available)
    );

    const filteredItems = computed(() => {
        let items = menuItems.value.filter(item => item.is_available);
        
        // Filter by category
        if (selectedCategory.value !== 'all') {
            items = items.filter(item => item.category_id === selectedCategory.value);
        }
        
        // Filter by search
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            items = items.filter(item => 
                item.name.toLowerCase().includes(query) || 
                item.short_name?.toLowerCase().includes(query) || 
                item.sku?.toLowerCase().includes(query)
            );
        }
        
        return items;
    });

    const featuredItems = computed(() => 
        menuItems.value.filter(item => item.is_featured && item.is_available)
    );

    const itemsByCategory = computed(() => {
        const grouped = {};
        activeCategories.value.forEach(cat => {
            grouped[cat.id] = menuItems.value
                .filter(item => item.category_id === cat.id && item.is_available)
                .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        });
        return grouped;
    });

    const itemsWithSpecials = computed(() => {
        return menuItems.value.map(item => {
            const special = dailySpecials.value.find(s => s.menu_item_id === item.id);
            if (special) {
                return {
                    ...item,
                    special_price: special.special_price,
                    special_title: special.title,
                    original_price: item.price,
                };
            }
            return item;
        });
    });

    const getItemById = computed(() => (id) => 
        menuItems.value.find(item => item.id === id)
    );

    const getCategoryById = computed(() => (id) => 
        categories.value.find(cat => cat.id === id)
    );

    const getItemsByCategory = computed(() => (categoryId) => 
        menuItems.value.filter(item => item.category_id === categoryId && item.is_available)
    );

    const getModifiersByGroup = computed(() => 
        modifiers.value.reduce((groups, mod) => {
            const group = mod.group_name;
            if (!groups[group]) groups[group] = [];
            groups[group].push(mod);
            return groups;
        }, {})
    );

    const getItemModifiers = computed(() => (itemId) => {
        const item = menuItems.value.find(i => i.id === itemId);
        if (!item?.modifiers) return [];
        return item.modifiers;
    });

    const activeSpecials = computed(() => {
        const now = new Date();
        return dailySpecials.value.filter(special => {
            if (!special.is_active) return false;
            const startDate = new Date(special.start_date);
            const endDate = new Date(special.end_date);
            return now >= startDate && now <= endDate;
        });
    });

    // ═══════════════════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════════════════
    
    /**
     * Fetch all menu data from API
     */
async function fetchMenu(forceRefresh = false) {
    if (!forceRefresh && lastFetched.value && categories.value.length > 0) {
        return true;
    }

    isLoading.value = true;
    error.value = null;
    
    try {
        const branchId = authStore.branchId;
        const params = branchId ? { branch_id: branchId } : {};

        // Fetch categories and menu items first (required)
        const [catRes, itemsRes] = await Promise.all([
            api.get('/categories', { params }),
            api.get('/menu-items', { params }),
        ]);
        
        categories.value = catRes.data.data || [];
        menuItems.value = itemsRes.data.data || [];

        // Fetch modifiers separately (optional - don't block if fails)
        try {
            const modsRes = await api.get('/modifiers', { params });
            modifiers.value = modsRes.data.data || [];
        } catch (e) {
            console.warn('Modifiers fetch failed:', e);
            modifiers.value = [];
        }

        // Fetch daily specials separately (optional)
        try {
            const specialsRes = await api.get('/daily-specials', { params });
            dailySpecials.value = specialsRes.data.data || [];
        } catch (e) {
            console.warn('Daily specials fetch failed:', e);
            dailySpecials.value = [];
        }

        lastFetched.value = new Date().toISOString();
        
        // Store offline
        await storeMenuOffline();
        
        return true;
    } catch (e) {
        console.error('Failed to fetch menu:', e);
        error.value = e.message;
        
        if (!navigator.onLine) {
            await loadMenuOffline();
        }
        
        return false;
    } finally {
        isLoading.value = false;
    }
}

    async function fetchAllMenu(forceRefresh = false) {
        return fetchMenu(forceRefresh);
    }

    /**
     * Search menu items
     */
    function searchItems(query) {
        searchQuery.value = query;
        if (!query || query.length < 2) return [];
        
        const lowerQuery = query.toLowerCase();
        return menuItems.value.filter(item => 
            item.is_available && (
                item.name.toLowerCase().includes(lowerQuery) || 
                item.short_name?.toLowerCase().includes(lowerQuery) || 
                item.sku?.toLowerCase().includes(lowerQuery) ||
                item.barcode?.includes(query)
            )
        );
    }

    function search(query) {
        searchQuery.value = query;
    }

    /**
     * Select category
     */
    function selectCategory(categoryId) {
        selectedCategory.value = categoryId;
    }

    /**
     * Find item by barcode
     */
    async function findByBarcode(barcode) {
        // Try local first
        const localItem = menuItems.value.find(item => item.barcode === barcode);
        if (localItem) return localItem;
        
        // Try API
        try {
            const response = await api.post('/menu-items/barcode', { barcode });
            if (response.data.success) {
                return response.data.data;
            }
        } catch (e) {
            console.error('Barcode lookup failed:', e);
        }
        
        return null;
    }

    /**
     * Get current price (with special if applicable)
     */
    function getCurrentPrice(itemId) {
        return getItemPrice(itemId);
    }

    function getItemPrice(itemId) {
        const item = menuItems.value.find(i => i.id === itemId);
        if (!item) return 0;
        
        const special = dailySpecials.value.find(s => s.menu_item_id === itemId);
        if (special) {
            if (special.is_active === false) return parseFloat(item.price);
            
            const now = new Date();
            const startDate = new Date(special.start_date);
            const endDate = new Date(special.end_date);
            
            if (now >= startDate && now <= endDate) {
                return parseFloat(special.special_price);
            }
        }
        
        return parseFloat(item.price);
    }

    /**
     * Store menu data offline
     */
    async function storeMenuOffline() {
        try {
            const db = useOfflineDb();
            
            await db.transaction('rw', [db.categories, db.menuItems, db.modifiers], async () => {
                await db.categories.clear();
                await db.categories.bulkPut(categories.value);
                
                await db.menuItems.clear();
                await db.menuItems.bulkPut(menuItems.value);
                
                await db.modifiers.clear();
                await db.modifiers.bulkPut(modifiers.value);
            });
            
            console.log('Menu stored offline');
        } catch (e) {
            console.error('Failed to store menu offline:', e);
        }
    }

    /**
     * Load menu from offline storage
     */
    async function loadMenuOffline() {
        try {
            const db = useOfflineDb();
            
            const [offlineCategories, offlineItems, offlineModifiers] = await Promise.all([
                db.categories.toArray(),
                db.menuItems.toArray(),
                db.modifiers.toArray()
            ]);
            
            if (offlineCategories.length) categories.value = offlineCategories;
            if (offlineItems.length) menuItems.value = offlineItems;
            if (offlineModifiers.length) modifiers.value = offlineModifiers;
            
            console.log('Menu loaded from offline');
            return true;
        } catch (e) {
            console.error('Failed to load menu offline:', e);
            return false;
        }
    }

    function loadFromOffline() {
        return loadMenuOffline();
    }

    /**
     * Clear search and filters
     */
    function clearFilters() {
        searchQuery.value = '';
        selectedCategory.value = 'all';
    }

    function clearMenu() {
        categories.value = [];
        menuItems.value = [];
        modifiers.value = [];
        dailySpecials.value = [];
        lastFetched.value = null;
    }

    // ═══════════════════════════════════════════════════════
    // RETURN
    // ═══════════════════════════════════════════════════════
    return {
        // State
        categories,
        menuItems,
        modifiers,
        dailySpecials,
        selectedCategory,
        searchQuery,
        isLoading,
        lastFetched,
        error,
        
        // Getters
        activeCategories,
        availableItems,
        filteredItems,
        featuredItems,
        itemsByCategory,
        itemsWithSpecials,
        getItemById,
        getCategoryById,
        getItemsByCategory,
        getModifiersByGroup,
        getItemModifiers,
        activeSpecials,
        
        // Actions
        fetchMenu,
        fetchAllMenu,
        search,
        searchItems,
        selectCategory,
        findByBarcode,
        getCurrentPrice,
        getItemPrice,
        storeMenuOffline,
        loadMenuOffline,
        loadFromOffline,
        clearFilters,
        clearMenu,
    };
}, {
    persist: {
        key: 'menu',
        paths: ['lastFetched'],
        storage: localStorage
    }
});
